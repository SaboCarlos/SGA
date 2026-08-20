<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Substitui a notificacao por omissao do Laravel (US-04): essa depende de
 * uma rota web nomeada "password.reset" que este backend, sendo so API, nao
 * tem -- geraria RouteNotFoundException. Aqui a ligacao aponta directamente
 * para o ecra do frontend que trata a redefinicao.
 */
class RedefinirPalavraPasse extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.url'), '/').'/redefinir-palavra-passe'
            .'?token='.$this->token
            .'&email='.urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Recuperação de palavra-passe — SGA')
            ->greeting("Olá, {$notifiable->nome}.")
            ->line('Recebemos um pedido de recuperação da sua palavra-passe.')
            ->action('Definir nova palavra-passe', $url)
            ->line('Se não foi você a pedir, ignore esta mensagem — a palavra-passe actual continua válida.');
    }
}
