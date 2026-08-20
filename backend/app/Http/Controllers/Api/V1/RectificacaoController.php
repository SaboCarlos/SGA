<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Classificacao;
use App\Models\PedidoRectificacao;
use App\Servicos\ServicoAuditoria;
use Illuminate\Http\Request;

/** UC23, UC24 (US-19). */
class RectificacaoController extends Controller
{
    public function __construct(private readonly ServicoAuditoria $auditoria) {}

    public function index(Request $request)
    {
        $query = PedidoRectificacao::query()->with(['classificacao.inscricao.estudante', 'solicitante', 'decisor']);

        $query->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')));

        return response()->json($query->get()->map(fn (PedidoRectificacao $p) => $this->paraResposta($p)));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'classificacao_id' => ['required', 'uuid', 'exists:classificacoes,id'],
            'valor_novo' => ['required', 'numeric', 'min:0', 'max:20'],
            'justificacao' => ['required', 'string', 'min:20'],
        ]);

        $classificacao = Classificacao::findOrFail($dados['classificacao_id']);

        $pedido = PedidoRectificacao::create([
            ...$dados,
            'solicitado_por' => $request->user()->id,
            'valor_anterior' => $classificacao->valor,
            'estado' => 'PENDENTE',
        ]);

        return response()->json($this->paraResposta($pedido->fresh(['classificacao.inscricao.estudante', 'solicitante'])), 201);
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

        $ehCoordenador = $utilizador->docente && $curso->coordenador_id === $utilizador->docente->id;
        abort_unless($ehCoordenador || $utilizador->temPerfil('ADMINISTRADOR'), 403, 'Apenas o Coordenador do curso da turma pode decidir.');

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

        return response()->json($this->paraResposta($rectificacao->fresh(['classificacao.inscricao.estudante', 'solicitante', 'decisor'])));
    }

    private function paraResposta(PedidoRectificacao $pedido): array
    {
        return [
            'id' => $pedido->id,
            'classificacao_id' => $pedido->classificacao_id,
            'estudante' => $pedido->classificacao->inscricao->estudante->nome_completo,
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
