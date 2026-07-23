@extends('layouts.client')

@section('title', 'Proyectos')

@php
    $statusLabels = ['activo' => 'Activo', 'en_revision' => 'En revisión', 'finalizado' => 'Finalizado'];
    $statusBadge = [
        'activo' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'en_revision' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'finalizado' => 'border-white/15 bg-white/5 text-zinc-400',
    ];
@endphp

@section('client-content')

  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">CometaX</p>
      <h1 class="mt-2 text-3xl font-semibold tracking-tight">Proyectos</h1>
    </div>
  </div>

  <div class="mt-8 grid gap-4 sm:grid-cols-2">
    @forelse ($projects as $project)
      <div class="rounded-card border border-white/10 bg-white/[0.03] p-6 transition hover:border-white/25">
        <div class="flex items-start justify-between gap-3">
          <a href="{{ route('client.projects.show', $project) }}" class="text-base font-semibold tracking-tight hover:underline underline-offset-4">{{ $project->name }}</a>
          <span class="shrink-0 inline-flex items-center rounded-full border px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-widest {{ $statusBadge[$project->status] ?? $statusBadge['finalizado'] }}">{{ $statusLabels[$project->status] ?? $project->status }}</span>
        </div>

        <div class="mt-4 h-1.5 w-full rounded-full bg-white/10 overflow-hidden"><div class="h-full rounded-full bg-white/70" style="width: {{ (int) $project->progress_percent }}%"></div></div>
        <p class="mt-2 font-mono text-xs text-zinc-500">{{ $project->progress_percent }}% · {{ $project->hours_used }}/{{ $project->hours_budgeted }} h</p>

        <div class="mt-4 flex items-center justify-between border-t border-white/5 pt-4">
          <div>
            <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Entrega estimada</p>
            <p class="mt-1 text-sm text-zinc-200">{{ $project->estimated_delivery?->format('d/m/Y') ?? '—' }}</p>
          </div>
          <div class="text-right">
            <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Incidencias</p>
            <p class="mt-1 text-sm {{ $project->incidents_count > 0 ? 'text-amber-300' : 'text-zinc-200' }}">{{ $project->incidents_count }}</p>
          </div>
        </div>
      </div>
    @empty
      <div class="sm:col-span-2 rounded-card border border-white/10 bg-white/[0.03] p-12 text-center text-sm text-zinc-500">Aún no hay proyectos.</div>
    @endforelse
  </div>

@endsection
