<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Acceso demo al portal de cliente, SIN Google. Entra como la cuenta demo
 * sembrada por DemoSeeder (demo@cometax.click), scoped a su propio tenant.
 *
 * Gateado por features.demo_login (env DEMO_LOGIN). OFF por defecto: es un
 * bypass de login. Con el flag apagado, 404.
 */
class DemoController extends Controller
{
    public function entrar(): RedirectResponse
    {
        abort_unless(config('features.demo_login'), 404);

        $demo = User::where('email', 'demo@cometax.click')->first();

        abort_if($demo === null, 404, 'Falta sembrar la cuenta demo (DemoSeeder).');

        // Cierra cualquier sesión de staff para no mezclar guards.
        Auth::guard('consultant')->logout();
        Auth::guard('web')->login($demo);
        request()->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
