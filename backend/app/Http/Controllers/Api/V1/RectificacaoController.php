<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Classificacao;
use App\Models\Curso;
use App\Models\PedidoRectificacao;
use App\Models\Utilizador;
use App\Servicos\ServicoAuditoria;
use Illuminate\Http\Request;

/** UC23, UC24 (US-19). */
class RectificacaoController extends Controller
{
    public function __construct(private readonly ServicoAuditoria $auditoria) {}

    public function index(Request $request)
    {
        $query = PedidoRectificacao::query()
            ->with(['classificacao.inscricao.estudante.curso', 'classificacao.avaliacao.turma.disciplina', 'solicitante', 'decisor'])
            ->orderByDesc('criado_em');

        $query->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')));

        // A direccao ve todos. O docente ve os pedidos sobre as suas
        // disciplinas e, se coordena um curso, todos os desse curso (e ele
        // quem os decide). O estudante so ve os seus.
        $utilizador = $request->user();
        if ($utilizador->docente && ! $utilizador->ehDireccao()) {
            $docenteId = $utilizador->docente->id;
            $query->where(fn ($q) => $q
                ->whereHas('classificacao.avaliacao.turma', fn ($t) => $t->where('docente_id', $docenteId))
                ->orWhereHas('classificacao.inscricao.estudante.curso', fn ($c) => $c->where('coordenador_id', $docenteId)));
        } elseif (! $utilizador->ehDireccao()) {
            abort_unless($utilizador->estudante !== null, 403, 'Sem permissão para consultar pedidos de rectificação.');
            $query->whereHas('classificacao.inscricao', fn ($q) => $q->where('estudante_id', $utilizador->estudante->id));
        }

        return response()->json($query->get()->map(fn (PedidoRectificacao $p) => $this->paraResposta($p, $utilizador)));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'classificacao_id' => ['required', 'uuid', 'exists:classificacoes,id'],
            'valor_novo' => ['required', 'numeric', 'min:0', 'max:20'],
            'justificacao' => ['required', 'string', 'min:20'],
        ]);

        $classificacao = Classificacao::findOrFail($dados['classificacao_id']);

        $utilizador = $request->user();
        abort_unless(
            $utilizador->gereTurma($classificacao->avaliacao->turma)
                || ($utilizador->estudante && $classificacao->inscricao->estudante_id === $utilizador->estudante->id),
            403,
            'Só pode pedir a rectificação de uma nota sua ou das suas turmas.'
        );

        $pedido = PedidoRectificacao::create([
            ...$dados,
            'solicitado_por' => $utilizador->id,
            'valor_anterior' => $classificacao->valor,
            'estado' => 'PENDENTE',
        ]);

        return response()->json($this->paraResposta($pedido->fresh(['classificacao.inscricao.estudante', 'solicitante']), $utilizador), 201);
    }

    public function decidir(Request $request, PedidoRectificacao $rectificacao)
    {
        $dados = $request->validate([
            'decisao' => ['required', 'in:APROVAR,RECUSAR'],
            'motivo' => ['required_if:decisao,RECUSAR', 'nullable', 'string'],
        ]);

        $classificacao = $rectificacao->classificacao;
        $turma = $classificacao->avaliacao->turma;
        $curso = $classificacao->inscricao->estudante->curso;
        $utilizador = $request->user();

        abort_unless($this->podeDecidir($utilizador, $curso), 403, 'Apenas o Coordenador do curso da turma pode decidir.');

        app('db')->transaction(function () use ($dados, $rectificacao, $classificacao, $utilizador): void {
            if ($dados['decisao'] === 'APROVAR') {
                $valorAnterior = $classificacao->valor;
                $classificacao->update(['valor' => $rectificacao->valor_novo, 'estado' => 'RECTIFICADA']);

                $this->auditoria->registar(
                    'classificacao.rectificada',
                    'classificacao',
                    $classificacao->id,
                    ['valor' => $valorAnterior],
                    ['valor' => $rectificacao->valor_novo],
                    $rectificacao->justificacao,
                );
            }

            $rectificacao->update([
                'estado' => $dados['decisao'] === 'APROVAR' ? 'APROVADO' : 'RECUSADO',
                'decidido_por' => $utilizador->id,
                'decidido_em' => now(),
                'motivo_decisao' => $dados['motivo'] ?? null,
            ]);
        });

        return response()->json($this->paraResposta($rectificacao->fresh(['classificacao.inscricao.estudante', 'solicitante', 'decisor']), $utilizador));
    }

    /** US-19: decide o Coordenador do curso do estudante (ou o Administrador). */
    private function podeDecidir(Utilizador $utilizador, ?Curso $curso): bool
    {
        return $utilizador->temPerfil('ADMINISTRADOR')
            || ($utilizador->docente !== null && $curso !== null && $curso->coordenador_id === $utilizador->docente->id);
    }

    private function paraResposta(PedidoRectificacao $pedido, ?Utilizador $utilizador = null): array
    {
        $turma = $pedido->classificacao->avaliacao->turma;

        return [
            'id' => $pedido->id,
            'classificacao_id' => $pedido->classificacao_id,
            'estudante' => $pedido->classificacao->inscricao->estudante->nome_completo,
            'disciplina' => $turma->disciplina->codigo.' — '.$turma->disciplina->designacao,
            'avaliacao' => $pedido->classificacao->avaliacao->designacao,
            'pode_decidir' => $utilizador !== null
                && $pedido->estado === 'PENDENTE'
                && $this->podeDecidir($utilizador, $pedido->classificacao->inscricao->estudante->curso),
            'valor_anterior' => $pedido->valor_anterior,
            'valor_novo' => $pedido->valor_novo,
            'justificacao' => $pedido->justificacao,
            'estado' => $pedido->estado,
            'solicitado_por' => $pedido->solicitante?->nome,
            'decidido_por' => $pedido->decisor?->nome,
            'decidido_em' => optional($pedido->decidido_em)->toIso8601String(),
        ];
    }
}
