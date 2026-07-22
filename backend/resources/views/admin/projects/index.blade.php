@extends('layouts.admin')

@section('title', 'Proyectos')

@php
    $statusLabels = ['activo' => 'Activo', 'en_revision' => 'En revisión', 'finalizado' => 'Finalizado'];
    $statusBadge = [
        'activo' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'en_revision' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'finalizado' => 'border-zinc-500/30 bg-zinc-500/10 text-zinc-300',
    ];
@endphp

@section('admin-content')

  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Proyectos</h1>
      <p class="mt-1 text-sm text-zinc-400">Todo lo que está en marcha.</p>
    </div>
    <a href="{{ route('admin.projects.create') }}"
       class="h-11 flex items-center rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">
      + Nuevo proyecto
    </a>
  </div>

  <form method="GET" action="{{ route('admin.projects.index') }}" class="mt-6 flex flex-wrap gap-2">
    <select name="status"
      class="h-11 rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
      <option value="">Todos los estados</option>
      @foreach ($statusLabels as $value => $label)
        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
      @endforeach
    </select>
    <select name="client_id"
      class="h-11 rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
      <option value="">Todos los clientes</option>
      @foreach ($clients as $client)
        <option value="{{ $client->id }}" @selected($clientId == $client->id)>{{ $client->name }}</option>
      @endforeach
    </select>
    <button class="h-11 rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
      Filtrar
    </button>
  </form>

  <div class="mt-6 overflow-x-auto rounded-card border border-white/10 bg-white/[0.03]">
    <table class="w-full min-w-[700px] text-left text-sm">
      <thead>
        <tr class="border-b border-white/10 font-mono text-[11px] uppercase tracking-widest text-zinc-500">
          <th class="px-6 py-4 font-medium">Proyecto</th>
          <th class="px-6 py-4 font-medium">Estado</th>
          <th class="px-6 py-4 font-medium">Avance</th>
          <th class="px-6 py-4 font-medium">Horas</th>
          <th class="px-6 py-4 font-medium">Entrega estimada</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/5">
        @forelse ($projects as $project)
          <tr class="transition hover:bg-white/[0.02]">
            <td class="px-6 py-4">
              <a href="{{ route('admin.projects.show', $project) }}" class="font-medium hover:underline underline-offset-4">{{ $project->name }}</a>
              <p class="font-mono text-xs text-zinc-500">{{ $project->client?->name ?? '—' }}</p>
            </td>
            <td class="px-6 py-4">
              <span class="inline-flex rounded-full border px-3 py-1 font-mono text-[10px] uppercase tracking-widest {{ $statusBadge[$project->status] ?? $statusBadge['finalizado'] }}">
                {{ $statusLabels[$project->status] ?? $project->status }}
              </span>
            </td>
            <td class="px-6 py-4 font-mono text-zinc-400">{{ $project->progress_percent }}%</td>
            <td class="px-6 py-4 font-mono text-zinc-400">{{ $project->hours_used }}/{{ $project->hours_budgeted }}h</td>
            <td class="px-6 py-4 font-mono text-xs text-zinc-400">{{ $project->estimated_delivery?->format('d/m/Y') ?? '—' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-6 py-16 text-center text-sm text-zinc-500">
              {{ ($status !== '' || $clientId !== '') ? 'Sin proyectos con esos filtros.' : 'Todavía no hay proyectos. Crea el primero.' }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

@endsection
