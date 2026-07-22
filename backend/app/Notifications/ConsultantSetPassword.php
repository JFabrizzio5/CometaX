<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo para que un consultant (staff) defina o restablezca su contraseña.
 * Enlaza a la ruta de staff (admin.password.reset), no a la de clientes.
 */
class ConsultantSetPassword extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('admin.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expira = config('auth.passwords.consultants.expire', 60);

        return (new MailMessage)
            ->subject('Define tu contraseña · CometaX')
            ->greeting('Acceso al panel interno')
            ->line('Recibimos una solicitud para definir la contraseña de tu cuenta de staff en CometaX.')
            ->action('Definir contraseña', $url)
            ->line("Este enlace vence en {$expira} minutos.")
            ->line('Si no esperabas este correo, puedes ignorarlo.');
    }
}
