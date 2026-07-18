<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePaymentsAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('features.payments_maintenance')) {
            return response()->json([
                'status' => 'maintenance',
                'message' => 'La facturación y los pagos están en mantenimiento. Vuelve a intentarlo más tarde.',
            ], 503);
        }

        return $next($request);
    }
}
