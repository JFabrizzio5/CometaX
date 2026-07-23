<?php

namespace App\Actions;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Adjunta evidencia a una incidencia: una imagen subida y/o un enlace externo
 * (Drive, YouTube…). Se usa igual desde el admin y desde el portal del cliente.
 */
class AttachIncidentEvidence
{
    /** @return int número de adjuntos creados */
    public function __invoke(Incident $incident, Request $request, string $source): int
    {
        $request->validate([
            'image' => ['nullable', 'required_without:link', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'link' => ['nullable', 'required_without:image', 'url', 'max:1000'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $count = 0;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('incidencias', 'public');
            $incident->attachments()->create([
                'kind' => 'image',
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'label' => $request->input('label'),
                'source' => $source,
            ]);
            $count++;
        }

        if ($request->filled('link')) {
            $incident->attachments()->create([
                'kind' => 'link',
                'url' => $request->input('link'),
                'label' => $request->input('label') ?: 'Enlace',
                'source' => $source,
            ]);
            $count++;
        }

        return $count;
    }
}
