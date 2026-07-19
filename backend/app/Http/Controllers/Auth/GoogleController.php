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
use RuntimeException;
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
        } catch (RuntimeException $e) {
            // Los RuntimeException de ResolveGoogleAccount llevan mensajes
            // pensados para el usuario final.
            return redirect()->route('login')->withErrors(['google' => $e->getMessage()]);
        } catch (Throwable $e) {
            // Cualquier otra cosa (fallo de base, etc.) no se muestra: el
            // mensaje puede filtrar credenciales o estructura interna.
            Log::error('Fallo al resolver la cuenta de Google', ['exception' => $e]);

            return redirect()->route('login')
                ->withErrors(['google' => 'No pudimos completar el acceso. Intenta de nuevo.']);
        }

        // Una sola identidad por navegador: si quedaba sesión del otro guard,
        // se cierra. Sin esto, un cliente que entra en la máquina de un
        // consultor hereda el acceso al panel interno.
        Auth::guard($guard === 'consultant' ? 'web' : 'consultant')->logout();

        Auth::guard($guard)->login($account, remember: true);
        $request->session()->regenerate();

        return redirect()->to($this->destino($request, $guard));
    }

    /**
     * Destino tras iniciar sesión.
     *
     * Solo se respeta la URL guardada por el middleware si el guard que acaba
     * de entrar puede servirla. Un cliente que intentó abrir /admin/* tenía esa
     * URL guardada; devolverlo ahí lo rebota a login, que la vuelve a guardar,
     * y el ciclo no termina nunca.
     */
    private function destino(Request $request, string $guard): string
    {
        $propia = $guard === 'consultant' ? route('admin.subscriptions') : route('dashboard');
        $intentada = $request->session()->pull('url.intended');

        if (! is_string($intentada) || $intentada === '') {
            return $propia;
        }

        // Solo URLs de este mismo host: 'url.intended' no debe convertirse en
        // un redirect abierto.
        $host = parse_url($intentada, PHP_URL_HOST);
        if ($host !== null && $host !== $request->getHost()) {
            return $propia;
        }

        $esDeAdmin = str_starts_with(
            (string) parse_url($intentada, PHP_URL_PATH),
            (string) parse_url(route('admin.subscriptions'), PHP_URL_PATH),
        );

        return $esDeAdmin === ($guard === 'consultant') ? $intentada : $propia;
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
