<?php

namespace App\Providers;

use App\Models\Client;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Client (empresa/tenant) es el modelo facturable, no User.
        Cashier::useCustomerModel(Client::class);

        // Correo de verificación en español.
        VerifyEmail::toMailUsing(fn ($notifiable, string $url) => (new MailMessage)
            ->subject('Verifica tu correo · CometaX')
            ->greeting('Bienvenido a CometaX')
            ->line('Confirma tu dirección de correo para activar tu cuenta y entrar al portal.')
            ->action('Verificar correo', $url)
            ->line('Si no creaste esta cuenta, puedes ignorar este mensaje.'));
    }
}
