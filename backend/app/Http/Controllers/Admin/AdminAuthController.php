<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Login email+password para staff (guard consultant) y flujo de definir/
 * restablecer contraseña. Los consultants se provisionan (seeder / alta desde
 * el admin) — no hay auto-registro de staff.
 */
class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('consultant')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Credenciales inválidas.']);
        }

        $request->session()->regenerate();

        // Una sola identidad por navegador: cierra sesión de cliente si la había.
        Auth::guard('web')->logout();

        return redirect()->intended(route('admin.subscriptions'));
    }

    public function showForgot(): View
    {
        return view('admin.auth.forgot');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Siempre responde igual, exista o no el correo: no filtra qué correos
        // son staff.
        Password::broker('consultants')->sendResetLink($request->only('email'));

        return back()->with('status', 'Si el correo pertenece a un miembro del equipo, te enviamos un enlace para definir tu contraseña.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('admin.auth.reset', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('consultants')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($consultant, string $password): void {
                $consultant->forceFill([
                    'password' => $password, // el cast 'hashed' lo encripta al guardar
                ])->setRememberToken(Str::random(60));

                $consultant->save();

                event(new PasswordReset($consultant));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return redirect()->route('admin.login')->with('status', 'Contraseña definida. Ya puedes iniciar sesión.');
    }
}
