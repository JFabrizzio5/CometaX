<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureConsultantIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $consultant = Auth::guard('consultant')->user();

        if ($consultant === null) {
            // Un cliente ya autenticado no necesita iniciar sesión: le falta
            // permiso, no sesión. Mandarlo a login guardaría esta URL como
            // "intended" y el callback lo devolvería acá en bucle.
            if (Auth::guard('web')->check()) {
                abort(403, 'Esta sección es solo para el equipo interno.');
            }

            return redirect()->guest(route('admin.login'));
        }

        // El rol vive en la base, no en la lista de correos de config: quitar un
        // correo de ADMIN_EMAILS no revoca el acceso de un Consultant ya creado.
        if (! $consultant->isStaff()) {
            abort(403, 'Esta sección es solo para el equipo interno.');
        }

        return $next($request);
    }
}
