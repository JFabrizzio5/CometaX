<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * Entrada al demo del portal. Manda al front estático (public_html/panel/app),
 * un mockup navegable servido directo por Apache — no usa sesión ni backend.
 *
 * Gateado por features.demo_login (env DEMO_LOGIN, OFF por defecto): con el
 * flag apagado, 404 y el botón no aparece en el login.
 */
class DemoController extends Controller
{
    public function entrar(): RedirectResponse
    {
        abort_unless(config('features.demo_login'), 404);

        // APP_URL ya incluye el /panel; un path con slash inicial lo duplicaría.
        return redirect(rtrim(config('app.url'), '/').'/app/');
    }
}
