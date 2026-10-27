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
use App\Http\Controllers\Api\V1\NotaController;
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
        Route::get('/notas', NotaController::class);

        Route::apiResource('estudantes', EstudanteController::class)->only(['index', 'show'])->parameters(['estudantes' => 'estudante']);
        Route::apiResource('estudantes', EstudanteController::class)->only(['store', 'update', 'destroy'])->parameters(['estudantes' => 'estudante'])
            ->middleware('perfil:ADMINISTRADOR,COORDENADOR');
        Route::get('/estudantes/{estudante}/aproveitamento', [EstudanteController::class, 'aproveitamento']);

        Route::apiResource('docentes', DocenteController::class)->only(['index', 'show'])->parameters(['docentes' => 'docente'])
            ->middleware('perfil:ADMINISTRADOR,COORDENADOR');

        Route::apiResource('cursos', CursoController::class)->only(['index', 'show']);
        Route::get('/cursos/{curso}/plano-curricular', [CursoController::class, 'planoCurricular']);

        Route::apiResource('disciplinas', DisciplinaController::class)->only(['index', 'show']);
        Route::get('/disciplinas/{disciplina}/pre-requisitos', [DisciplinaController::class, 'preRequisitos']);

        Route::apiResource('turmas', TurmaController::class)->only(['index', 'show']);
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

        Route::apiResource('anos-lectivos', AnoLectivoController::class)->only(['index', 'show'])->parameters(['anos-lectivos' => 'anoLectivo']);
        Route::get('/anos-lectivos/{anoLectivo}/periodos', [AnoLectivoController::class, 'periodos']);

        // Gestao institucional: cadastros, oferta formativa e calendario.
        // So o Administrador altera; a leitura (acima) fica aberta porque o
        // catalogo nao tem dados pessoais e alimenta os ecras de todos.
        Route::middleware('perfil:ADMINISTRADOR')->group(function (): void {
            Route::apiResource('docentes', DocenteController::class)->only(['store', 'update', 'destroy'])->parameters(['docentes' => 'docente']);

            Route::apiResource('cursos', CursoController::class)->only(['store', 'update']);
            Route::post('/cursos/{curso}/plano-curricular', [CursoController::class, 'acrescentarAoPlano']);

            Route::apiResource('disciplinas', DisciplinaController::class)->only(['store', 'update']);
            Route::post('/disciplinas/{disciplina}/pre-requisitos', [DisciplinaController::class, 'definirPreRequisito']);

            Route::post('/turmas', [TurmaController::class, 'store']);

            Route::post('/anos-lectivos', [AnoLectivoController::class, 'store']);
            Route::post('/anos-lectivos/{anoLectivo}/activar', [AnoLectivoController::class, 'activar']);
            Route::post('/anos-lectivos/{anoLectivo}/encerrar', [AnoLectivoController::class, 'encerrar']);
            Route::post('/anos-lectivos/{anoLectivo}/periodos', [AnoLectivoController::class, 'criarPeriodo']);
        });
    });
});
