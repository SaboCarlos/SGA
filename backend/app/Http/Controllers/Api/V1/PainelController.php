<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Curso;
use App\Models\Estudante;
use App\Models\Inscricao;
use App\Models\PedidoRectificacao;
use App\Models\Turma;
use Illuminate\Http\Request;

/** UC30 (US-27, US-23). Indicadores por perfil; um utilizador com vários perfis ve todos. */
class PainelController extends Controller
{
    public function __invoke(Request $request)
    {
        $utilizador = $request->user();
        $dados = [];

        if ($utilizador->estudante) {
            $estudante = $utilizador->estudante;

            $dados += [
                'disciplinas_inscritas' => $estudante->inscricoes()->where('estado', 'ACTIVA')->count(),
                'disciplinas_aprovadas' => $estudante->inscricoes()->where('situacao_final', 'APROVADO')->count(),
                'estado' => $estudante->estado,
            ];
        }

        if ($utilizador->docente) {
            $docente = $utilizador->docente;
            $turmasIds = Turma::where('docente_id', $docente->id)->pluck('id');

            $dados += [
                'turmas_atribuidas' => $turmasIds->count(),
                'avaliacoes_por_publicar' => Avaliacao::whereIn('turma_id', $turmasIds)
                    ->where('estado', '<>', 'PUBLICADA')
                    ->count(),
            ];

            // US-23: cursos coordenados pelo docente autenticado — visao
            // agregada, distinta da lista de turmas que lecciona.
            $cursosCoordenados = Curso::where('coordenador_id', $docente->id)->get();

            if ($cursosCoordenados->isNotEmpty()) {
                $dados['cursos_coordenados'] = $cursosCoordenados->map(function (Curso $curso) {
                    $estudantesIds = Estudante::where('curso_id', $curso->id)->pluck('id');

                    return [
                        'curso' => $curso->designacao,
                        'total_estudantes' => $estudantesIds->count(),
                        'inscricoes_activas' => Inscricao::whereIn('estudante_id', $estudantesIds)->where('estado', 'ACTIVA')->count(),
                        'rectificacoes_pendentes' => PedidoRectificacao::whereHas(
                            'classificacao.inscricao.estudante',
                            fn ($q) => $q->where('curso_id', $curso->id)
                        )->where('estado', 'PENDENTE')->count(),
                    ];
                })->values();
            }
        }

        if ($utilizador->temPerfil('ADMINISTRADOR')) {
            $dados += [
                'total_estudantes' => Estudante::count(),
                'total_inscricoes_activas' => Inscricao::where('estado', 'ACTIVA')->count(),
                'total_turmas' => Turma::count(),
            ];
        }

        return response()->json($dados);
    }
}
