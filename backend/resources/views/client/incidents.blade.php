@extends('layouts.client')

@section('title', 'Incidencias')

@php
    $priorityLabels = ['baja' => 'Baja', 'media' => 'Media', 'urgente' => 'Urgente'];
    $priorityColors = [
        'urgente' => 'border-red-500/30 bg-red-500/10 text-red-300',
        'media'   => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'baja'    => 'border-zinc-500/30 bg-zinc-500/10 text-zinc-400',
    ];
    $statusLabels = ['nuevo' => 'Nuevo', 'revision' => 'Revisión', 'progreso' => 'En progreso', 'resuelto' => 'Resuelto'];
    $statusColors = [
        'nuevo'    => 'border-sky-500/30 bg-sky-500/10 text-sky-300',
        'revision' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'progreso' => 'border-violet-500/30 bg-violet-500/10 text-violet-300',
        'resuelto' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
    ];
    $badge = 'inline-flex items-center rounded-full border px-2 py-0.5 font-mono text-[10px] uppercase tracking-widest';
@endphp

@section('client-content')

  <div>
    <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Soporte técnico</p>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight">Incidencias</h1>
    <p class="mt-1 text-sm text-zinc-400">Reporta problemas y da seguimiento a tus tickets.</p>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-3">

    <section class="lg:col-span-2 rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Tus incidencias</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($incidents as $incident)
          <li class="py-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span class="font-mono text-xs text-zinc-500">{{ $incident->ticket_code }}</span>
              <div class="flex items-center gap-2">
                <span class="{{ $badge }} {{ $priorityColors[$incident->priority] ?? 'border-zinc-500/30 bg-zinc-500/10 text-zinc-400' }}">{{ $priorityLabels[$incident->priority] ?? $incident->priority }}</span>
                <span class="{{ $badge }} {{ $statusColors[$incident->status] ?? 'border-zinc-500/30 bg-zinc-500/10 text-zinc-400' }}">{{ $statusLabels[$incident->status] ?? $incident->status }}</span>
              </div>
            </div>
            <p class="mt-2 text-sm font-medium text-zinc-100">{{ $incident->title }}</p>
            <p class="mt-1 font-mono text-xs text-zinc-500">{{ $incident->project?->name ?? 'Sin proyecto' }} · {{ $incident->created_at->format('d/m/Y') }}</p>
          </li>
        @empty
          <li class="py-8 text-center text-sm text-zinc-500">Aún no has reportado incidencias.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Reportar incidencia</h2>
      <form method="POST" action="{{ route('client.incidents.store') }}" class="mt-4 space-y-4">
        @csrf

        <div>
          <label for="project_id" class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Proyecto</label>
          <select id="project_id" name="project_id" class="mt-2 w-full rounded-control border border-white/15 bg-white/[0.03] px-3 py-2.5 text-sm text-white focus:border-white/30 focus:outline-none [&>option]:bg-zinc-900">
            <option value="">Selecciona un proyecto</option>
            @foreach ($projects as $project)
              <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label for="title" class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Título</label>
          <input id="title" name="title" type="text" value="{{ old('title') }}" class="mt-2 w-full rounded-control border border-white/15 bg-white/[0.03] px-3 py-2.5 text-sm text-white placeholder-zinc-500 focus:border-white/30 focus:outline-none" placeholder="Resumen del problema">
        </div>

        <div>
          <label for="description" class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Descripción</label>
          <textarea id="description" name="description" rows="4" class="mt-2 w-full rounded-control border border-white/15 bg-white/[0.03] px-3 py-2.5 text-sm text-white placeholder-zinc-500 focus:border-white/30 focus:outline-none" placeholder="Describe qué ocurre y cómo reproducirlo">{{ old('description') }}</textarea>
        </div>

        <div>
          <label for="priority" class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Prioridad</label>
          <select id="priority" name="priority" class="mt-2 w-full rounded-control border border-white/15 bg-white/[0.03] px-3 py-2.5 text-sm text-white focus:border-white/30 focus:outline-none [&>option]:bg-zinc-900">
            <option value="baja" @selected(old('priority') === 'baja')>Baja</option>
            <option value="media" @selected(old('priority', 'media') === 'media')>Media</option>
            <option value="urgente" @selected(old('priority') === 'urgente')>Urgente</option>
          </select>
        </div>

        <button type="submit" class="w-full h-11 rounded-control bg-white font-mono text-[11px] uppercase tracking-widest text-zinc-900 transition hover:bg-zinc-200">Reportar</button>
      </form>
    </section>

  </div>

@endsection
