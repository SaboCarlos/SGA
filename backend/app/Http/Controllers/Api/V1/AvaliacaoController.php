<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Dominio\Enums\TipoAvaliacao;
use App\Dominio\Excepcoes\RegraNegocioViolada;
use App\Dominio\Repositorios\RepositorioClassificacao;
use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Classificacao;
use App\Models\Inscricao;
use App\Models\Turma;
use App\Servicos\ServicoAvaliacao;
use Illuminate\Http\Request;

/** UC19, UC20, UC21 (US-16, US-17, US-18). */
class AvaliacaoController extends Controller
{
    public function __construct(private readonly ServicoAvaliacao $servicoAvaliacao) {}

    public function index(Request $request, Turma $turma)
    {
        // A estrutura (designacoes e pesos) nao tem notas: o estudante
        // inscrito na turma tambem a pode consultar.
        $utilizador = $request->user();
        $estaInscrito = $utilizador->estudante
            && $turma->inscricoes()->where('estudante_id', $utilizador->estudante->id)->exists();
        abort_unless($utilizador->gereTurma($turma) || $estaInscrito, 403, 'Sem acesso às avaliações desta turma.');

        $avaliacoes = $turma->avaliacoes;
        // RN-06: so os testes, trabalhos e praticas somam 100%; o exame conta a parte.
        $soma = round((float) $avaliacoes
            ->filter(fn ($a) => TipoAvaliacao::from((string) $a->getAttribute('tipo'))->contaParaFrequencia())
            ->sum('peso'), 2);

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

    /**
     * Grelha de lancamento: uma linha por estudante activo na turma, com a
     * nota se ja foi lancada. Antes devolvia so as classificacoes existentes,
     * e numa avaliacao nova a grelha vinha vazia -- o docente nao tinha onde
     * escrever a primeira nota.
     */
    public function classificacoes(Request $request, Avaliacao $avaliacao)
    {
        abort_unless($request->user()->gereTurma($avaliacao->turma), 403, 'Sem acesso às classificações desta turma.');

        $lancadas = $avaliacao->classificacoes()->get()->keyBy('inscricao_id');

        return response()->json(
            $avaliacao->turma->inscricoes()
                ->where('estado', 'ACTIVA')
                ->with('estudante')
                ->get()
                ->sortBy('estudante.numero_estudante')
                ->map(function (Inscricao $i) use ($lancadas) {
                    /** @var Classificacao|null $c */
                    $c = $lancadas->get($i->id);

                    return [
                        'id' => $c?->id,
                        'inscricao_id' => $i->id,
                        'numero_estudante' => $i->estudante->numero_estudante,
                        'nome_completo' => $i->estudante->nome_completo,
                        'valor' => $c?->valor,
                        'faltou' => (bool) $c?->faltou,
                        'estado' => $c?->estado,
                    ];
                })
                ->values()
        );
    }

    public function lancarClassificacoes(Request $request, Avaliacao $avaliacao)
    {
        $this->autorizarDocenteDaTurma($request, $avaliacao->turma);

        // O docente pode gravar o rascunho as vezes que precisar; so a
        // publicacao fecha a avaliacao (a partir dai, so por rectificacao).
        if ($avaliacao->estado === 'PUBLICADA') {
            return response()->json([
                'mensagem' => 'Estas notas já foram publicadas. Para corrigir uma nota, peça uma rectificação.',
            ], 409);
        }

        $dados = $request->validate([
            'classificacoes' => ['required', 'array', 'min:1'],
            'classificacoes.*.inscricao_id' => ['required', 'uuid', 'exists:inscricoes,id'],
            'classificacoes.*.valor' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'classificacoes.*.faltou' => ['sometimes', 'boolean'],
        ]);

        // So inscricoes desta turma: sem isto, o docente podia lancar notas
        // noutra turma enviando o id de uma inscricao alheia.
        $inscricoesDaTurma = $avaliacao->turma->inscricoes()->pluck('id')->all();
        $alheias = array_diff(array_column($dados['classificacoes'], 'inscricao_id'), $inscricoesDaTurma);
        abort_if($alheias !== [], 422, 'Há inscrições que não pertencem a esta turma.');

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

        $estrutura = $avaliacao->turma->avaliacoes
            ->map(fn (Avaliacao $a) => ['id' => (int) $a->id, 'tipo' => (string) $a->tipo, 'peso' => (float) $a->peso, 'estado' => (string) $a->estado])
            ->values()
            ->all();

        // Tudo ou nada: antes, se o calculo falhasse a meio, as notas ficavam
        // marcadas como publicadas (visiveis aos alunos) com a avaliacao ainda
        // "em lancamento".
        try {
            $resumo = app('db')->transaction(function () use ($avaliacao, $confirmarIncompleta, $estrutura) {
                $resumo = $this->servicoAvaliacao->publicar($avaliacao, $confirmarIncompleta, $estrutura);

                // "regulamento_avaliacao" foi atribuida acima so para o servico a ler;
                // nao e uma coluna real de "avaliacoes" e nao pode entrar no UPDATE
                // seguinte (ficaria marcada como "suja" pelo Eloquent).
                unset($avaliacao->regulamento_avaliacao);
                $avaliacao->update(['estado' => 'PUBLICADA']);

                return $resumo;
            });
        } catch (RegraNegocioViolada $e) {
            return response()->json($e->paraResposta(), 409);
        }

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
