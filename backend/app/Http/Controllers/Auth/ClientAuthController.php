<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Acceso de clientes con correo y contraseña (además de Google). El auto-
 * registro crea el espacio del cliente y exige verificar el correo antes de
 * entrar al portal.
 */
class ClientAuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Correo o contraseña incorrectos.']);
        }

        $request->session()->regenerate();
        Auth::guard('consultant')->logout();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        // Alta de un nuevo cliente (tenant) con su primer usuario como admin.
        $user = DB::transaction(function () use ($data): User {
            $client = Client::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'contact_email' => $data['email'],
            ]);

            return $client->users()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // cast 'hashed'
                'role' => 'admin',
            ]);
        });

        event(new Registered($user)); // envía el correo de verificación
        Auth::guard('web')->login($user);

        return redirect()->route('verification.notice');
    }

    public function notice(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('dashboard')->with('status', 'Correo verificado. ¡Bienvenido!');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Te reenviamos el correo de verificación.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'cliente';
        $slug = $base;
        $suffix = 2;

        while (Client::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
