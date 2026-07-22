<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Raíz del panel (/panel). Manda a cada quien a su sitio según el guard.
 *
 * Es un controller invokable, no un Closure: `php artisan route:cache` no
 * puede serializar un Closure en la ruta `/` y la deja respondiendo solo HEAD
 * (405 en GET). Con un controller la ruta se cachea sin romperse.
 */
class HomeRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        if (Auth::guard('consultant')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route(Auth::guard('web')->check() ? 'dashboard' : 'login');
    }
}
