<?php

namespace App\Console\Commands;

use App\Models\Consultant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

/**
 * Provisiona un admin de staff: crea el Consultant super_admin (si no existe)
 * y le manda el correo para que defina su contraseña.
 *
 *   php artisan cometax:provision-admin                # usa el primero de ADMIN_EMAILS
 *   php artisan cometax:provision-admin correo@dom.com # correo explícito
 */
class ProvisionAdmin extends Command
{
    protected $signature = 'cometax:provision-admin {email?} {--name=}';

    protected $description = 'Crea un Consultant super_admin y le envía el enlace para definir contraseña';

    public function handle(): int
    {
        $email = $this->argument('email')
            ?? (config('features.admin_emails')[0] ?? null);

        if (! $email) {
            $this->error('No hay correo: pásalo como argumento o define ADMIN_EMAILS.');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim($email));
        $name = $this->option('name') ?: 'Admin';

        $consultant = Consultant::firstOrNew(['email' => $email]);

        if (! $consultant->exists) {
            $consultant->name = $name;
        }

        // Siempre asegura super_admin (idempotente).
        $consultant->role = 'super_admin';
        $consultant->save();

        $this->info("Consultant super_admin listo: {$email}");

        $status = Password::broker('consultants')->sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->info('Correo "define tu contraseña" enviado.');

            return self::SUCCESS;
        }

        $this->error("No se pudo enviar el correo: {$status}");

        return self::FAILURE;
    }
}
