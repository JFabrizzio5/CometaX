<?php

namespace App\Actions\Auth;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;

/**
 * Traduce una cuenta de Google a una identidad de la aplicación.
 *
 * Devuelve el guard con el que hay que iniciar sesión:
 *   'consultant' → staff interno (panel de administración)
 *   'web'        → cliente (portal de cliente)
 */
class ResolveGoogleAccount
{
    /**
     * @return array{guard: string, account: Model}
     */
    public function __invoke(SocialiteUser $googleUser): array
    {
        $email = mb_strtolower(trim((string) $googleUser->getEmail()));

        if ($email === '') {
            throw new RuntimeException('Google no devolvió una dirección de correo.');
        }

        // Google marca email_verified=false en cuentas de Workspace cuyo dominio
        // aún no completó la verificación. Aceptarlas permitiría suplantar el
        // correo de un admin, así que se rechazan.
        if (! ($googleUser->user['email_verified'] ?? false)) {
            throw new RuntimeException('La cuenta de Google no tiene el correo verificado.');
        }

        if (in_array($email, config('features.admin_emails'), true)) {
            return ['guard' => 'consultant', 'account' => $this->resolveConsultant($googleUser, $email)];
        }

        return ['guard' => 'web', 'account' => $this->resolveUser($googleUser, $email)];
    }

    private function resolveConsultant(SocialiteUser $googleUser, string $email): Consultant
    {
        $consultant = Consultant::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($consultant === null) {
            return Consultant::create([
                'name' => $googleUser->getName() ?: $email,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar_path' => $googleUser->getAvatar(),
                'role' => 'super_admin',
            ]);
        }

        $consultant->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar_path' => $googleUser->getAvatar() ?: $consultant->avatar_path,
        ])->save();

        return $consultant;
    }

    private function resolveUser(SocialiteUser $googleUser, string $email): User
    {
        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if ($user !== null) {
            return $user;
        }

        // El correo ya existe pero fue creado a mano (invitación del equipo):
        // se enlaza la cuenta de Google en lugar de duplicar el usuario. Es
        // seguro porque Google ya verificó la propiedad del correo.
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ])->save();

            return $user;
        }

        $this->guardSignupDomain($email);

        return $this->createTenant($googleUser, $email);
    }

    private function guardSignupDomain(string $email): void
    {
        $allowed = config('features.allowed_signup_domains');

        if ($allowed === []) {
            return;
        }

        $domain = Str::after($email, '@');

        if (! in_array($domain, $allowed, true)) {
            throw new RuntimeException("El dominio {$domain} no está habilitado para registrarse.");
        }
    }

    /**
     * Alta nueva: se crea el Client (entidad facturable de Cashier) junto con
     * su primer usuario, que queda como admin de su propia organización.
     */
    private function createTenant(SocialiteUser $googleUser, string $email): User
    {
        return DB::transaction(function () use ($googleUser, $email): User {
            $name = $googleUser->getName() ?: Str::before($email, '@');

            $client = Client::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'contact_email' => $email,
            ]);

            return $client->users()->create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'cliente';
        $slug = $base;
        $suffix = 2;

        while (Client::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
