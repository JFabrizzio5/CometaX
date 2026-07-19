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
            return redirect()->guest(route('login'));
        }

        // El rol vive en la base, no en la lista de correos de config: quitar un
        // correo de ADMIN_EMAILS no revoca el acceso de un Consultant ya creado.
        if (! $consultant->isStaff()) {
            abort(403, 'Esta sección es solo para el equipo interno.');
        }

        return $next($request);
    }
}
