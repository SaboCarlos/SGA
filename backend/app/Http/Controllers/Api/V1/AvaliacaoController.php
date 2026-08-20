<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Dominio\Excepcoes\RegraNegocioViolada;
use App\Dominio\Repositorios\RepositorioClassificacao;
use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Classificacao;
use App\Models\Turma;
use App\Servicos\ServicoAvaliacao;
use Illuminate\Http\Request;

/** UC19, UC20, UC21 (US-16, US-17, US-18). */
class AvaliacaoController extends Controller
{
    public function __construct(private readonly ServicoAvaliacao $servicoAvaliacao) {}

    public function index(Turma $turma)
    {
        $avaliacoes = $turma->avaliacoes;
        $soma = round((float) $avaliacoes->sum('peso'), 2);

        return response()->json([
            'soma_pesos' => $soma,
            'estrutura_valida' => abs($soma - 100.0) < 0.01,
            'avaliacoes' => $avaliacoes->map(fn (Avaliacao $a) => $this->avaliacaoParaResposta($a))->values(),
        ]);
    }

    public function store(Request $request, Turma $turma)
    {
        $this->autorizarDocenteDaTurma($request, $turma);

        $dados = $request->validate([
            'designacao' => ['required', 'string', 'max:60'],
            'tipo' => ['required', 'in:TESTE,TRABALHO,PRATICA,EXAME'],
            'peso' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'data_prevista' => ['nullable', 'date'],
        ]);

        $avaliacao = $turma->avaliacoes()->create([...$dados, 'estado' => 'RASCUNHO']);

        return response()->json($this->avaliacaoParaResposta($avaliacao), 201);
    }

    public function classificacoes(Avaliacao $avaliacao)
    {
        return response()->json(
            $avaliacao->classificacoes()
                ->with('inscricao.estudante')
                ->get()
                ->map(fn (Classificacao $c) => [
                    'id' => $c->id,
                    'inscricao_id' => $c->inscricao_id,
                    'numero_estudante' => $c->inscricao->estudante->numero_estudante,
                    'nome_completo' => $c->inscricao->estudante->nome_completo,
                    'valor' => $c->valor,
                    'faltou' => $c->faltou,
                    'estado' => $c->estado,
                ])
        );
    }

    public function lancarClassificacoes(Request $request, Avaliacao $avaliacao)
    {
        $this->autorizarDocenteDaTurma($request, $avaliacao->turma);

        if ($avaliacao->estado !== 'RASCUNHO') {
            return response()->json([
                'mensagem' => 'Só é possível lançar classificações numa avaliação em rascunho.',
            ], 409);
        }

        $dados = $request->validate([
            'classificacoes' => ['required', 'array', 'min:1'],
            'classificacoes.*.inscricao_id' => ['required', 'uuid', 'exists:inscricoes,id'],
            'classificacoes.*.valor' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'classificacoes.*.faltou' => ['sometimes', 'boolean'],
        ]);

        $utilizador = $request->user();

        $lote = array_map(fn (array $c) => [
            'avaliacao_id' => $avaliacao->id,
            'inscricao_id' => $c['inscricao_id'],
            'valor' => $c['valor'] ?? null,
            'faltou' => $c['faltou'] ?? false,
            'lancada_por' => $utilizador->id,
        ], $dados['classificacoes']);

        app(RepositorioClassificacao::class)->guardarLote($lote);

        $avaliacao->update(['estado' => 'ACTIVA']);

        return response()->json(['mensagem' => 'Classificações gravadas em rascunho.']);
    }

    public function publicar(Request $request, Avaliacao $avaliacao)
    {
        $this->autorizarDocenteDaTurma($request, $avaliacao->turma);

        $primeiraInscricao = $avaliacao->turma->inscricoes()->with('estudante.curso')->first();
        $avaliacao->regulamento_avaliacao = $primeiraInscricao?->estudante?->curso?->regulamento_avaliacao ?? 'PADRAO_ESCN';

        $confirmarIncompleta = (bool) $request->boolean('confirmar_incompleta');

        try {
            $resumo = $this->servicoAvaliacao->publicar($avaliacao, $confirmarIncompleta);
        } catch (RegraNegocioViolada $e) {
            return response()->json($e->paraResposta(), 409);
        }

        // "regulamento_avaliacao" foi atribuida acima so para o servico a ler;
        // nao e uma coluna real de "avaliacoes" e nao pode entrar no UPDATE
        // seguinte (ficaria marcada como "suja" pelo Eloquent).
        unset($avaliacao->regulamento_avaliacao);
        $avaliacao->update(['estado' => 'PUBLICADA']);

        $mediaTurma = round((float) collect($avaliacao->turma->inscricoes)->avg('classificacao_final'), 1);

        return response()->json([...$resumo, 'media_turma' => is_nan($mediaTurma) ? null : $mediaTurma]);
    }

    private function autorizarDocenteDaTurma(Request $request, Turma $turma): void
    {
        $utilizador = $request->user();

        $ehResponsavel = $utilizador->docente && $utilizador->docente->id === $turma->docente_id;

        abort_unless($ehResponsavel || $utilizador->temPerfil('ADMINISTRADOR'), 403, 'O docente não é o responsável da turma.');
    }

    private function avaliacaoParaResposta(Avaliacao $avaliacao): array
    {
        return [
            'id' => $avaliacao->id,
            'designacao' => $avaliacao->designacao,
            'tipo' => $avaliacao->tipo,
            'peso' => $avaliacao->peso,
            'data_prevista' => optional($avaliacao->data_prevista)->toDateString(),
            'estado' => $avaliacao->estado,
        ];
    }
}
