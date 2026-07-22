@extends('layouts.admin')

@section('title', 'Incidencias')

@php
    $priorityBadge = [
        'urgente' => 'border-red-500/30 bg-red-500/10 text-red-300',
        'media' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'baja' => 'border-zinc-500/30 bg-zinc-500/10 text-zinc-300',
    ];
    $statusBadge = [
        'nuevo' => 'border-sky-500/30 bg-sky-500/10 text-sky-300',
        'revision' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'progreso' => 'border-violet-500/30 bg-violet-500/10 text-violet-300',
        'resuelto' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
    ];
@endphp

@section('admin-content')

  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Incidencias</h1>
      <p class="mt-1 text-sm text-zinc-400">Tickets reportados por proyecto.</p>
    </div>
    <a href="{{ route('admin.incidents.create') }}"
       class="h-11 flex items-center rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">
      + Nueva incidencia
    </a>
  </div>

  <form method="GET" action="{{ route('admin.incidents.index') }}" class="mt-6 flex flex-wrap gap-2">
    <select name="estado"
      class="h-11 rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
      <option value="">Todos los estados</option>
      <option value="nuevo" @selected($estado === 'nuevo')>Nuevo</option>
      <option value="revision" @selected($estado === 'revision')>En revisión</option>
      <option value="progreso" @selected($estado === 'progreso')>En progreso</option>
      <option value="resuelto" @selected($estado === 'resuelto')>Resuelto</option>
    </select>
    <select name="prioridad"
      class="h-11 rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
      <option value="">Todas las prioridades</option>
      <option value="urgente" @selected($prioridad === 'urgente')>Urgente</option>
      <option value="media" @selected($prioridad === 'media')>Media</option>
      <option value="baja" @selected($prioridad === 'baja')>Baja</option>
    </select>
    <button class="h-11 rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
      Filtrar
    </button>
  </form>

  <div class="mt-6 overflow-x-auto rounded-card border border-white/10 bg-white/[0.03]">
    <table class="w-full min-w-[900px] text-left text-sm">
      <thead>
        <tr class="border-b border-white/10 font-mono text-[11px] uppercase tracking-widest text-zinc-500">
          <th class="px-6 py-4 font-medium">Ticket</th>
          <th class="px-6 py-4 font-medium">Título</th>
          <th class="px-6 py-4 font-medium">Prioridad</th>
          <th class="px-6 py-4 font-medium">Estado</th>
          <th class="px-6 py-4 font-medium">Asignado</th>
          <th class="px-6 py-4 font-medium">Creada</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/5">
        @forelse ($incidents as $incident)
          <tr class="transition hover:bg-white/[0.02]">
            <td class="px-6 py-4 font-mono text-xs text-zinc-300">{{ $incident->ticket_code }}</td>
            <td class="px-6 py-4">
              <a href="{{ route('admin.incidents.edit', $incident) }}" class="font-medium hover:underline underline-offset-4">{{ $incident->title }}</a>
              <p class="font-mono text-xs text-zinc-500">{{ $incident->project?->name ?? '—' }} · {{ $incident->project?->client?->name ?? '—' }}</p>
            </td>
            <td class="px-6 py-4">
              <span class="inline-flex rounded-full border px-2.5 py-1 font-mono text-[10px] uppercase tracking-widest {{ $priorityBadge[$incident->priority] ?? 'border-white/15 text-zinc-300' }}">
                {{ $incident->priority }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span class="inline-flex rounded-full border px-2.5 py-1 font-mono text-[10px] uppercase tracking-widest {{ $statusBadge[$incident->status] ?? 'border-white/15 text-zinc-300' }}">
                {{ $incident->status }}
              </span>
            </td>
            <td class="px-6 py-4 text-zinc-300">{{ $incident->assignee?->name ?? '—' }}</td>
            <td class="px-6 py-4 font-mono text-xs text-zinc-400">{{ $incident->created_at->format('d/m/Y') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-6 py-16 text-center text-sm text-zinc-500">
              {{ ($estado !== '' || $prioridad !== '') ? 'Sin incidencias con esos filtros.' : 'Todavía no hay incidencias. Crea la primera.' }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

@endsection
