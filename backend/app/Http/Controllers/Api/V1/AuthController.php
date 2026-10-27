<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UtilizadorResource;
use App\Models\Utilizador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** UC01, UC04. */
class AuthController extends Controller
{
    private const MAX_TENTATIVAS = 5;

    private const BLOQUEIO_SEGUNDOS = 900; // 15 minutos

    public function login(Request $request)
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'palavra_passe' => ['required', 'string'],
        ]);

        $chave = 'login:'.mb_strtolower($dados['email']);

        // US-01: 5 tentativas falhadas seguidas bloqueiam a conta por 15
        // minutos -- o bloqueio e por email, nao por IP, porque o objectivo
        // e proteger a conta, nao apenas atrasar quem a esta a tentar
        // adivinhar a partir de um unico endereco.
        if (RateLimiter::tooManyAttempts($chave, self::MAX_TENTATIVAS)) {
            return response()->json([
                'mensagem' => 'Conta bloqueada temporariamente após várias tentativas falhadas. Tente novamente dentro de alguns minutos.',
                'segundos_restantes' => RateLimiter::availableIn($chave),
            ], 423);
        }

        $utilizador = Utilizador::where('email', $dados['email'])->first();

        if (! $utilizador
            || ! $utilizador->activo
            || ! Hash::check($dados['palavra_passe'], $utilizador->getAuthPassword())
        ) {
            RateLimiter::hit($chave, self::BLOQUEIO_SEGUNDOS);

            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorrectas.'],
            ]);
        }

        RateLimiter::clear($chave);

        $minutos = (int) config('sanctum.expiracao_minutos');
        $expiraEm = now()->addMinutes($minutos);

        $token = $utilizador->createToken('api', ['*'], $expiraEm);

        $utilizador->forceFill(['ultimo_acesso' => now()])->save();

        return response()->json([
            'token' => $token->plainTextToken,
            'expira_em' => $expiraEm->toIso8601String(),
            'utilizador' => new UtilizadorResource($utilizador),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return new UtilizadorResource($request->user());
    }

    /**
     * A resposta e sempre 202, exista ou nao o e-mail (US-04) -- nao revela
     * quais as contas registadas. O broker do Laravel envia mesmo a
     * mensagem (RedefinirPalavraPasse) quando o utilizador existe.
     */
    public function recuperarPalavraPasse(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $estado = Password::broker('utilizadores')->sendResetLink(['email' => $request->string('email')]);

        Log::info('Pedido de recuperação de palavra-passe', ['email' => (string) $request->string('email'), 'estado' => $estado]);

        return response()->json(status: 202);
    }

    public function redefinirPalavraPasse(Request $request)
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'palavra_passe' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // O PasswordBroker do Laravel reconhece apenas a chave literal
        // "password" -- para a excluir da pesquisa do utilizador e para a
        // extrair para o callback. "palavra_passe" (o nome usado em todo o
        // resto da API) nao e reconhecida, e entraria na query de procura
        // como mais uma coluna a comparar.
        $estado = Password::broker('utilizadores')->reset(
            [
                'email' => $dados['email'],
                'token' => $dados['token'],
                'password' => $dados['palavra_passe'],
            ],
            function (Utilizador $utilizador, string $palavraPasse): void {
                $utilizador->forceFill(['palavra_passe' => Hash::make($palavraPasse)])->save();
                $utilizador->tokens()->delete();
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['O token de recuperação é inválido ou já expirou.'],
            ]);
        }

        return response()->json(['mensagem' => 'Palavra-passe redefinida com sucesso.']);
    }
}
