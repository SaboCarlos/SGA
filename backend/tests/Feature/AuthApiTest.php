<?php

declare(strict_types=1);

use App\Models\Utilizador;
use App\Notifications\RedefinirPalavraPasse;
use Database\Seeders\CenarioAcademicoSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->seed(CenarioAcademicoSeeder::class);
});

it('autentica com credenciais validas e devolve um token', function () {
    $resposta = $this->postJson('/api/v1/auth/login', [
        'email' => 'ana@escn.ac.mz',
        'palavra_passe' => 'palavra-passe',
    ]);

    $resposta->assertOk()
        ->assertJsonStructure(['token', 'expira_em', 'utilizador' => ['id', 'nome', 'email', 'perfis', 'permissoes']])
        ->assertJsonPath('utilizador.email', 'ana@escn.ac.mz');
});

it('recusa palavra-passe errada sem indicar qual o campo incorrecto', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'ana@escn.ac.mz',
        'palavra_passe' => 'errada',
    ])->assertStatus(422);
});

it('recusa email inexistente com a mesma resposta generica', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'ninguem@escn.ac.mz',
        'palavra_passe' => 'qualquer',
    ])->assertStatus(422);
});

it('devolve os dados do utilizador autenticado em /auth/me', function () {
    $utilizador = Utilizador::where('email', 'ana@escn.ac.mz')->first();

    $this->actingAs($utilizador)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('email', 'ana@escn.ac.mz')
        ->assertJsonPath('perfis.0', 'ESTUDANTE')
        ->assertJsonMissing(['data']);
});

it('termina a sessao e invalida o token', function () {
    $utilizador = Utilizador::where('email', 'ana@escn.ac.mz')->first();
    $token = $utilizador->createToken('teste')->plainTextToken;

    $resposta = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout');

    $resposta->assertNoContent();

    // O guard "sanctum" cacheia o utilizador resolvido durante o pedido
    // anterior, dentro do mesmo processo de teste; sem isto, o segundo
    // pedido reutilizava a resolucao ja feita, mesmo com o token apagado.
    auth()->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('aceita sempre o pedido de recuperacao, exista ou nao o email (US-04)', function (string $email) {
    $this->postJson('/api/v1/auth/palavra-passe/recuperar', ['email' => $email])
        ->assertStatus(202);
})->with([
    'ana@escn.ac.mz',
    'ninguem@escn.ac.mz',
]);

it('envia mesmo a notificacao de recuperacao quando o email existe (US-04)', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/palavra-passe/recuperar', ['email' => 'ana@escn.ac.mz'])
        ->assertStatus(202);

    Notification::assertSentTo(
        Utilizador::where('email', 'ana@escn.ac.mz')->first(),
        RedefinirPalavraPasse::class
    );
});

it('nao envia nenhuma notificacao quando o email nao existe', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/palavra-passe/recuperar', ['email' => 'ninguem@escn.ac.mz'])
        ->assertStatus(202);

    Notification::assertNothingSent();
});

it('redefine a palavra-passe com um token valido e invalida os tokens antigos', function () {
    $utilizador = Utilizador::where('email', 'ana@escn.ac.mz')->first();
    $tokenAntigo = $utilizador->createToken('sessao-antiga')->plainTextToken;
    $token = Password::broker('utilizadores')->createToken($utilizador);

    $this->postJson('/api/v1/auth/palavra-passe/redefinir', [
        'email' => 'ana@escn.ac.mz',
        'token' => $token,
        'palavra_passe' => 'nova-palavra-passe-forte',
        'palavra_passe_confirmation' => 'nova-palavra-passe-forte',
    ])->assertOk();

    expect(Hash::check('nova-palavra-passe-forte', $utilizador->fresh()->getAuthPassword()))->toBeTrue();
    expect($utilizador->tokens()->count())->toBe(0);

    auth()->forgetGuards();
    $this->withHeader('Authorization', "Bearer {$tokenAntigo}")
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});

it('recusa redefinir a palavra-passe com um token invalido', function () {
    $this->postJson('/api/v1/auth/palavra-passe/redefinir', [
        'email' => 'ana@escn.ac.mz',
        'token' => 'token-invalido',
        'palavra_passe' => 'nova-palavra-passe-forte',
        'palavra_passe_confirmation' => 'nova-palavra-passe-forte',
    ])->assertStatus(422);
});

it('bloqueia a conta por 15 minutos apos 5 tentativas falhadas (US-01)', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'ana@escn.ac.mz',
            'palavra_passe' => 'errada',
        ])->assertStatus(422);
    }

    $resposta = $this->postJson('/api/v1/auth/login', [
        'email' => 'ana@escn.ac.mz',
        'palavra_passe' => 'palavra-passe', // mesmo a correcta: a conta ja esta bloqueada
    ]);

    $resposta->assertStatus(423)->assertJsonStructure(['mensagem', 'segundos_restantes']);
});

it('nao bloqueia contas diferentes por tentativas falhadas de uma delas', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'ana@escn.ac.mz',
            'palavra_passe' => 'errada',
        ]);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'bruno@escn.ac.mz',
        'palavra_passe' => 'palavra-passe',
    ])->assertOk();
});
