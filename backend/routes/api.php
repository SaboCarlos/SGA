<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AnoLectivoController;
use App\Http\Controllers\Api\V1\AuditoriaController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvaliacaoController;
use App\Http\Controllers\Api\V1\CursoController;
use App\Http\Controllers\Api\V1\DisciplinaController;
use App\Http\Controllers\Api\V1\DocenteController;
use App\Http\Controllers\Api\V1\EstudanteController;
use App\Http\Controllers\Api\V1\InscricaoController;
use App\Http\Controllers\Api\V1\NotificacaoController;
use App\Http\Controllers\Api\V1\PainelController;
use App\Http\Controllers\Api\V1\PautaController;
use App\Http\Controllers\Api\V1\PerfilController;
use App\Http\Controllers\Api\V1\PermissaoController;
use App\Http\Controllers\Api\V1\RectificacaoController;
use App\Http\Controllers\Api\V1\RelatorioController;
use App\Http\Controllers\Api\V1\TurmaController;
use App\Http\Controllers\Api\V1\UtilizadorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas da API v1
|--------------------------------------------------------------------------
| Espelham docs/openapi.yaml (Fase 3). Nenhuma rota de negocio e anonima
| (RNF-11) -- so /auth/login e /auth/palavra-passe/recuperar ficam fora do
| grupo auth:sanctum.
*/

Route::prefix('v1')->group(function (): void {

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/palavra-passe/recuperar', [AuthController::class, 'recuperarPalavraPasse']);
    Route::post('/auth/palavra-passe/redefinir', [AuthController::class, 'redefinirPalavraPasse']);

    Route::middleware('auth:sanctum')->group(function (): void {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/painel', PainelController::class);

        Route::apiResource('estudantes', EstudanteController::class)->parameters(['estudantes' => 'estudante']);
        Route::get('/estudantes/{estudante}/aproveitamento', [EstudanteController::class, 'aproveitamento']);

        Route::apiResource('docentes', DocenteController::class)->parameters(['docentes' => 'docente']);

        Route::apiResource('cursos', CursoController::class)->only(['index', 'store', 'show', 'update']);
        Route::get('/cursos/{curso}/plano-curricular', [CursoController::class, 'planoCurricular']);
        Route::post('/cursos/{curso}/plano-curricular', [CursoController::class, 'acrescentarAoPlano']);

        Route::apiResource('disciplinas', DisciplinaController::class)->only(['index', 'store', 'show', 'update']);
        Route::get('/disciplinas/{disciplina}/pre-requisitos', [DisciplinaController::class, 'preRequisitos']);
        Route::post('/disciplinas/{disciplina}/pre-requisitos', [DisciplinaController::class, 'definirPreRequisito']);

        Route::apiResource('turmas', TurmaController::class)->only(['index', 'store', 'show']);
        Route::get('/turmas/{turma}/inscritos', [TurmaController::class, 'inscritos']);
        Route::get('/turmas/{turma}/pauta', [PautaController::class, 'show']);

        Route::get('/inscricoes/disponiveis', [InscricaoController::class, 'disponiveis']);
        Route::get('/inscricoes', [InscricaoController::class, 'index']);
        Route::post('/inscricoes', [InscricaoController::class, 'store']);
        Route::delete('/inscricoes/{inscricao}', [InscricaoController::class, 'destroy']);

        Route::get('/turmas/{turma}/avaliacoes', [AvaliacaoController::class, 'index']);
        Route::post('/turmas/{turma}/avaliacoes', [AvaliacaoController::class, 'store']);
        Route::get('/avaliacoes/{avaliacao}/classificacoes', [AvaliacaoController::class, 'classificacoes']);
        Route::put('/avaliacoes/{avaliacao}/classificacoes', [AvaliacaoController::class, 'lancarClassificacoes']);
        Route::post('/avaliacoes/{avaliacao}/publicar', [AvaliacaoController::class, 'publicar']);

        Route::get('/rectificacoes', [RectificacaoController::class, 'index']);
        Route::post('/rectificacoes', [RectificacaoController::class, 'store']);
        Route::post('/rectificacoes/{rectificacao}/decisao', [RectificacaoController::class, 'decidir']);

        Route::post('/pautas/{pauta}/homologar', [PautaController::class, 'homologar']);

        Route::get('/relatorios/aproveitamento-disciplina', [RelatorioController::class, 'aproveitamentoPorDisciplina']);

        Route::get('/notificacoes', [NotificacaoController::class, 'index']);
        Route::patch('/notificacoes/{notificacao}/lida', [NotificacaoController::class, 'marcarLida']);

        // RNF-32: a auditoria e so de leitura -- qualquer outro verbo cai no 405
        // devolvido pelo framework por nao haver rota correspondente definida.
        Route::get('/auditoria', [AuditoriaController::class, 'index']);
        Route::match(['post', 'put', 'patch', 'delete'], '/auditoria', [AuditoriaController::class, 'metodoNaoPermitido']);

        Route::apiResource('utilizadores', UtilizadorController::class)->parameters(['utilizadores' => 'utilizador']);

        Route::get('/permissoes', [PermissaoController::class, 'index']);
        Route::post('/permissoes', [PermissaoController::class, 'store']);

        Route::get('/perfis', [PerfilController::class, 'index']);
        Route::post('/perfis', [PerfilController::class, 'store']);
        Route::put('/perfis/{perfil}', [PerfilController::class, 'update']);
        Route::post('/perfis/{perfil}/permissoes', [PerfilController::class, 'atribuirPermissao']);
        Route::delete('/perfis/{perfil}/permissoes/{permissao}', [PerfilController::class, 'retirarPermissao']);

        Route::apiResource('anos-lectivos', AnoLectivoController::class)->only(['index', 'store', 'show'])->parameters(['anos-lectivos' => 'anoLectivo']);
        Route::post('/anos-lectivos/{anoLectivo}/activar', [AnoLectivoController::class, 'activar']);
        Route::post('/anos-lectivos/{anoLectivo}/encerrar', [AnoLectivoController::class, 'encerrar']);
        Route::get('/anos-lectivos/{anoLectivo}/periodos', [AnoLectivoController::class, 'periodos']);
        Route::post('/anos-lectivos/{anoLectivo}/periodos', [AnoLectivoController::class, 'criarPeriodo']);
    });
});
