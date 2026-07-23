@extends('layouts.client')

@section('title', $project->name)

@php
    $statusLabels = ['activo' => 'Activo', 'en_revision' => 'En revisión', 'finalizado' => 'Finalizado'];
    $statusBadge = [
        'activo' => 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300',
        'en_revision' => 'border-amber-400/20 bg-amber-400/10 text-amber-300',
        'finalizado' => 'border-white/15 bg-white/5 text-zinc-400',
    ];

    $prioLabels = ['baja' => 'Baja', 'media' => 'Media', 'urgente' => 'Urgente'];
    $prioBadge = [
        'urgente' => 'text-red-300 bg-red-400/10 border-red-400/20',
        'media' => 'text-amber-300 bg-amber-400/10 border-amber-400/20',
        'baja' => 'text-zinc-400 bg-white/5 border-white/15',
    ];

    $incidentColumns = ['nuevo' => 'Nuevo', 'revision' => 'En revisión', 'progreso' => 'En progreso', 'resuelto' => 'Resuelto'];

    $hoursPct = $project->hours_budgeted > 0 ? min(100, round($project->hours_used / $project->hours_budgeted * 100)) : 0;

    $initials = fn ($name) => collect(explode(' ', trim((string) $name)))
        ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp

@section('client-content')

  {{-- Header: link volver + título + badge de estado --}}
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <a href="{{ route('client.projects') }}" class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-white transition">← Proyectos</a>
      <div class="mt-2 flex items-center gap-3">
        <h1 class="text-3xl font-semibold tracking-tight">{{ $project->name }}</h1>
        <span class="inline-flex items-center rounded-full border px-3 py-1.5 font-mono text-[10px] uppercase tracking-widest {{ $statusBadge[$project->status] ?? $statusBadge['finalizado'] }}">{{ $statusLabels[$project->status] ?? $project->status }}</span>
      </div>
    </div>
  </div>

  <div class="mt-6 space-y-6">

    <!-- Resumen superior -->
    <div class="grid sm:grid-cols-3 gap-4">
      <div class="rounded-card bg-white/[0.03] border border-white/10 p-6">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Avance</p>
        <p class="text-3xl font-medium mt-3">{{ $project->progress_percent }}%</p>
        <div class="h-1.5 bg-white/10 rounded-full overflow-hidden mt-3"><div class="h-full bg-white rounded-full" style="width:{{ (int) $project->progress_percent }}%"></div></div>
      </div>
      <div class="rounded-card bg-white/[0.03] border border-white/10 p-6">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Horas usadas</p>
        <p class="text-3xl font-medium mt-3">{{ $project->hours_used }}<span class="text-base text-zinc-500">/{{ $project->hours_budgeted }}h</span></p>
        <div class="h-1.5 bg-white/10 rounded-full overflow-hidden mt-3"><div class="h-full bg-white rounded-full" style="width:{{ $hoursPct }}%"></div></div>
      </div>
      <div class="rounded-card bg-white/[0.03] border border-white/10 p-6">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Entrega estimada</p>
        <p class="text-3xl font-medium mt-3">{{ $project->estimated_delivery?->format('d M') ?? '—' }}</p>
        <p class="text-xs text-zinc-500 mt-1">Consultor: {{ $project->leadConsultant?->name ?? '—' }}</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex flex-wrap gap-2">
      <button data-tab="resumen" class="rounded-full bg-white text-black text-xs font-mono uppercase tracking-widest px-4 py-2 transition">Resumen</button>
      <button data-tab="documentos" class="rounded-full text-zinc-400 hover:text-white text-xs font-mono uppercase tracking-widest px-4 py-2 transition">Documentos</button>
      <button data-tab="horas" class="rounded-full text-zinc-400 hover:text-white text-xs font-mono uppercase tracking-widest px-4 py-2 transition">Horas</button>
      <button data-tab="actividad" class="rounded-full text-zinc-400 hover:text-white text-xs font-mono uppercase tracking-widest px-4 py-2 transition">Actividad</button>
      <button data-tab="incidencias" class="rounded-full text-zinc-400 hover:text-white text-xs font-mono uppercase tracking-widest px-4 py-2 transition">Incidencias</button>
    </div>

    <!-- Panel: resumen -->
    <div data-tab-panel="resumen" class="rounded-card bg-white/[0.03] border border-white/10 p-6">
      <h2 class="text-base font-semibold mb-3">Alcance del proyecto</h2>
      <p class="text-sm text-zinc-400 leading-relaxed mb-6">{{ $project->description ?? 'Sin descripción.' }}</p>
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="rounded-control border border-white/10 p-4">
          <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mb-2">Hitos</p>
          <ul class="space-y-2 text-sm">
            @forelse ($project->milestones as $milestone)
              @if ($milestone->status === 'done')
                <li class="flex items-center gap-2 text-zinc-300"><svg class="h-4 w-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>{{ $milestone->label }}</li>
              @elseif ($milestone->status === 'in_progress')
                <li class="flex items-center gap-2 text-white"><span class="h-4 w-4 rounded-full border-2 border-white flex items-center justify-center"><span class="h-1.5 w-1.5 bg-white rounded-full"></span></span>{{ $milestone->label }}</li>
              @else
                <li class="flex items-center gap-2 text-zinc-500"><span class="h-4 w-4 rounded-full border-2 border-white/20"></span>{{ $milestone->label }}</li>
              @endif
            @empty
              <li class="text-zinc-500">Sin hitos definidos.</li>
            @endforelse
          </ul>
        </div>
        <div class="rounded-control border border-white/10 p-4">
          <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mb-2">Equipo asignado</p>
          <div class="space-y-3">
            @forelse ($project->consultants as $consultant)
              <div class="flex items-center gap-3"><div class="h-8 w-8 rounded-full bg-white/10 flex items-center justify-center font-mono text-[10px]">{{ $initials($consultant->name) }}</div><div><p class="text-sm">{{ $consultant->name }}</p><p class="text-xs text-zinc-500">{{ $consultant->pivot->role_label }}</p></div></div>
            @empty
              <p class="text-sm text-zinc-500">Sin consultores asignados.</p>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <!-- Panel: documentos -->
    <div data-tab-panel="documentos" class="hidden rounded-card bg-white/[0.03] border border-white/10 p-6">
      <h2 class="text-base font-semibold mb-4">Documentos y contrato</h2>
      <div class="space-y-3">
        @forelse ($project->documents as $document)
          @php
            $docMeta = array_filter([
                $document->status,
                $document->version ? 'Versión '.$document->version : null,
                $document->signed_date?->format('d M Y'),
            ]);
          @endphp
          <div class="flex items-center gap-3 rounded-control border border-white/10 p-3.5">
            <svg class="h-5 w-5 text-zinc-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
            <div class="min-w-0"><p class="text-sm truncate">{{ $document->title }}</p><p class="text-xs text-zinc-500">{{ implode(' · ', $docMeta) }}</p></div>
            @if (\Illuminate\Support\Str::startsWith($document->file_path, 'http'))
              <a href="{{ $document->file_path }}" target="_blank" rel="noopener" class="ml-auto font-mono text-[10px] uppercase tracking-widest text-zinc-400 hover:text-white shrink-0">Descargar</a>
            @endif
          </div>
        @empty
          <p class="text-sm text-zinc-500 text-center py-8">Sin documentos.</p>
        @endforelse
      </div>
    </div>

    <!-- Panel: horas -->
    <div data-tab-panel="horas" class="hidden rounded-card bg-white/[0.03] border border-white/10 p-6">
      <h2 class="text-base font-semibold mb-4">Registro de horas</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left font-mono text-[10px] uppercase tracking-widest text-zinc-500 border-b border-white/10">
              <th class="pb-3 font-medium">Fecha</th>
              <th class="pb-3 font-medium">Actividad</th>
              <th class="pb-3 font-medium">Consultor</th>
              <th class="pb-3 font-medium text-right">Horas</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            @forelse ($project->timeEntries as $entry)
              <tr>
                <td class="py-3 text-zinc-400">{{ $entry->entry_date?->format('d M Y') }}</td>
                <td class="py-3">{{ $entry->activity }}</td>
                <td class="py-3 text-zinc-400">{{ $entry->consultant?->name ?? '—' }}</td>
                <td class="py-3 text-right">{{ $entry->hours }}h</td>
              </tr>
            @empty
              <tr><td colspan="4" class="py-8 text-center text-zinc-500">Sin horas registradas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Panel: actividad -->
    <div data-tab-panel="actividad" class="hidden rounded-card bg-white/[0.03] border border-white/10 p-6">
      <h2 class="text-base font-semibold mb-4">Actividad reciente</h2>
      <div class="space-y-5">
        @forelse ($project->activities as $activity)
          <div class="flex gap-3">
            <div class="h-2 w-2 rounded-full bg-white mt-1.5 shrink-0"></div>
            <div><p class="text-sm">{{ $activity->description ?? $activity->action }}</p><p class="text-xs text-zinc-500">{{ $activity->occurred_at?->format('d M H:i') }}</p></div>
          </div>
        @empty
          <p class="text-sm text-zinc-500 text-center py-8">Sin actividad reciente.</p>
        @endforelse
      </div>
    </div>

    <!-- Panel: incidencias -->
    <div data-tab-panel="incidencias" class="hidden rounded-card bg-white/[0.03] border border-white/10 p-6">
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-base font-semibold">Incidencias de este proyecto</h2>
        <a href="{{ route('client.incidents') }}" class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-white transition">Ver tablero completo →</a>
      </div>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($incidentColumns as $statusKey => $statusLabel)
          @php $columnIncidents = $incidentsByStatus->get($statusKey, collect()); @endphp
          <div class="rounded-control bg-white/[0.02] border border-white/10 p-4">
            <div class="flex items-center justify-between mb-3">
              <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-400">{{ $statusLabel }}</p>
              <span class="font-mono text-[10px] text-zinc-500">{{ $columnIncidents->count() }}</span>
            </div>
            @if ($columnIncidents->isEmpty())
              <p class="text-xs text-zinc-600 text-center py-6">Sin incidencias</p>
            @else
              <div class="space-y-2.5">
                @foreach ($columnIncidents as $incident)
                  <div class="rounded-control bg-white/[0.04] border border-white/10 p-3.5">
                    <div class="flex items-center justify-between mb-1.5">
                      <span class="font-mono text-[10px] uppercase tracking-widest rounded-full border px-2 py-0.5 {{ $prioBadge[$incident->priority] ?? $prioBadge['baja'] }}">{{ $prioLabels[$incident->priority] ?? $incident->priority }}</span>
                      <span class="font-mono text-[10px] text-zinc-600">{{ $incident->ticket_code }}</span>
                    </div>
                    <p class="text-sm font-medium">{{ $incident->title }}</p>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </div>
      <a href="{{ route('client.incidents') }}" class="mt-5 w-full flex items-center justify-center gap-2 rounded-full border border-white/15 text-sm font-medium py-3 hover:bg-white/5 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Reportar incidencia para este proyecto
      </a>
    </div>

  </div>

  <script>
    (function () {
      var tabs = document.querySelectorAll('[data-tab]');
      var panels = document.querySelectorAll('[data-tab-panel]');
      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          var name = tab.getAttribute('data-tab');
          tabs.forEach(function (t) {
            var active = t === tab;
            t.classList.toggle('bg-white', active);
            t.classList.toggle('text-black', active);
            t.classList.toggle('text-zinc-400', !active);
          });
          panels.forEach(function (p) {
            p.classList.toggle('hidden', p.getAttribute('data-tab-panel') !== name);
          });
        });
      });
    })();
  </script>

@endsection
