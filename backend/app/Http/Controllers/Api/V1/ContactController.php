<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Recibe los mensajes del formulario de la landing y los manda por correo al
 * buzón de CometaX. El rate limit (2/IP/hora) va en la ruta.
 */
class ContactController extends Controller
{
    private const TIPOS = [
        'client' => 'Cliente / diagnóstico',
        'investor' => 'Inversionista / VC',
        'grant' => 'Donativo / ONG',
        'other' => 'Otro',
    ];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'type' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $tipo = self::TIPOS[$data['type'] ?? ''] ?? ($data['type'] ?? 'Sin especificar');
        $destino = config('mail.from.address', 'cometax@cometax.click');

        $cuerpo = "Nuevo mensaje desde la landing\n\n"
            ."Nombre: {$data['name']}\n"
            ."Correo: {$data['email']}\n"
            ."Interés: {$tipo}\n\n"
            ."Mensaje:\n{$data['message']}\n";

        try {
            Mail::raw($cuerpo, function ($mail) use ($data, $destino, $tipo) {
                $mail->to($destino)
                    ->replyTo($data['email'], $data['name'])
                    ->subject("Landing · {$tipo} · {$data['name']}");
            });
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar contacto de la landing', ['exception' => $e]);

            return response()->json([
                'ok' => false,
                'message' => 'No pudimos enviar tu mensaje. Intenta por WhatsApp o correo directo.',
            ], 500);
        }

        // Guarda el lead si el modelo/tabla lo permiten (no crítico para el envío).
        try {
            Lead::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'notes' => $data['message'],
                'source' => 'consulta_publica',
            ]);
        } catch (\Throwable) {
            // Sin bloquear: el correo ya salió.
        }

        return response()->json(['ok' => true, 'message' => '¡Mensaje enviado! Te contactamos pronto.']);
    }
}
