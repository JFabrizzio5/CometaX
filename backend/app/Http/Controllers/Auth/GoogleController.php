<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResolveGoogleAccount;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request, ResolveGoogleAccount $resolve): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            // Sesión perdida entre el redirect y la vuelta (cookie caducada,
            // pestaña vieja). Reintentar es la salida correcta.
            return redirect()->route('auth.google.redirect');
        } catch (Throwable $e) {
            Log::warning('Fallo el callback de Google', ['exception' => $e]);

            return redirect()->route('login')
                ->withErrors(['google' => 'No pudimos completar el acceso con Google. Intenta de nuevo.']);
        }

        try {
            ['guard' => $guard, 'account' => $account] = $resolve($googleUser);
        } catch (Throwable $e) {
            return redirect()->route('login')->withErrors(['google' => $e->getMessage()]);
        }

        Auth::guard($guard)->login($account, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(
            $guard === 'consultant' ? route('admin.subscriptions') : route('dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('consultant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
