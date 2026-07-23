@extends('layouts.client')

@section('title', $project->name)

@php
    $statusLabels = ['activo' => 'Activo', 'en_revision' => 'En revisión', 'finalizado' => 'Finalizado'];
    $statusBadge = [
        'activo' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'en_revision' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'finalizado' => 'border-white/15 bg-white/5 text-zinc-400',
    ];

    $milestoneLabels = ['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'done' => 'Hecho'];
    $milestoneBadge = [
        'done' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'in_progress' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'pending' => 'border-white/15 bg-white/5 text-zinc-400',
    ];

    $incidentLabels = ['nuevo' => 'Nuevo', 'revision' => 'Revisión', 'progreso' => 'En progreso', 'resuelto' => 'Resuelto'];
    $incidentBadge = [
        'nuevo' => 'border-sky-500/30 bg-sky-500/10 text-sky-300',
        'revision' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'progreso' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'resuelto' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
    ];

    $priorityLabels = ['baja' => 'Baja', 'media' => 'Media', 'urgente' => 'Urgente'];
    $priorityBadge = [
        'urgente' => 'border-red-500/30 bg-red-500/10 text-red-300',
        'media' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'baja' => 'border-white/15 bg-white/5 text-zinc-400',
    ];
@endphp

@section('client-content')

  <a href="{{ route('client.projects') }}" class="font-mono text-[11px] uppercase tracking-widest text-zinc-500 hover:text-white transition">← Proyectos</a>

  <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
    <div>
      <div class="flex items-center gap-3">
        <h1 class="text-3xl font-semibold tracking-tight">{{ $project->name }}</h1>
        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-widest {{ $statusBadge[$project->status] ?? $statusBadge['finalizado'] }}">{{ $statusLabels[$project->status] ?? $project->status }}</span>
      </div>
      @if ($project->description)
        <p class="mt-2 max-w-2xl text-sm text-zinc-400">{{ $project->description }}</p>
      @endif
      @if ($project->leadConsultant)
        <p class="mt-2 font-mono text-xs text-zinc-500">Consultor líder: <span class="text-zinc-300">{{ $project->leadConsultant?->name }}</span></p>
      @endif
    </div>
  </div>

  <div class="mt-6 rounded-card border border-white/10 bg-white/[0.03] p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Avance</p>
      <p class="font-mono text-xs text-zinc-400">{{ $project->progress_percent }}% · {{ $project->hours_used }}/{{ $project->hours_budgeted }} h</p>
    </div>
    <div class="mt-3 h-1.5 w-full rounded-full bg-white/10 overflow-hidden"><div class="h-full rounded-full bg-white/70" style="width: {{ (int) $project->progress_percent }}%"></div></div>
    <div class="mt-4 grid grid-cols-2 gap-4 border-t border-white/5 pt-4">
      <div>
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Inicio</p>
        <p class="mt-1 text-sm text-zinc-200">{{ $project->start_date?->format('d/m/Y') ?? '—' }}</p>
      </div>
      <div>
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Entrega estimada</p>
        <p class="mt-1 text-sm text-zinc-200">{{ $project->estimated_delivery?->format('d/m/Y') ?? '—' }}</p>
      </div>
    </div>
  </div>

  <div class="mt-6 grid gap-6 lg:grid-cols-2">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Avances</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($project->activities as $act)
          <li class="py-3">
            <p class="text-sm text-zinc-200">{{ $act->description ?? $act->action }}</p>
            <p class="mt-1 font-mono text-xs text-zinc-500">
              {{ $act->occurred_at?->format('d/m H:i') }}
              @if ($act->actor instanceof \App\Models\Consultant) · {{ $act->actor->name }}@endif
            </p>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin avances todavía.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Hitos</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($project->milestones as $milestone)
          <li class="py-3 flex items-center justify-between gap-3">
            <p class="text-sm text-zinc-200">{{ $milestone->label }}</p>
            <span class="shrink-0 inline-flex items-center rounded-full border px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-widest {{ $milestoneBadge[$milestone->status] ?? $milestoneBadge['pending'] }}">{{ $milestoneLabels[$milestone->status] ?? $milestone->status }}</span>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin hitos definidos.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Incidencias</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($project->incidents as $incident)
          <li class="py-3">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm text-zinc-200">{{ $incident->title }}</p>
                <p class="mt-1 font-mono text-[10px] uppercase tracking-widest text-zinc-500">{{ $incident->ticket_code }}</p>
              </div>
              <div class="flex shrink-0 flex-col items-end gap-1.5">
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-widest {{ $incidentBadge[$incident->status] ?? $incidentBadge['nuevo'] }}">{{ $incidentLabels[$incident->status] ?? $incident->status }}</span>
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-widest {{ $priorityBadge[$incident->priority] ?? $priorityBadge['baja'] }}">{{ $priorityLabels[$incident->priority] ?? $incident->priority }}</span>
              </div>
            </div>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin incidencias.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Enlaces</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($project->links as $link)
          <li class="py-3 flex items-center justify-between gap-3">
            <a href="{{ $link->url }}" target="_blank" rel="noopener" class="text-sm text-zinc-200 hover:underline underline-offset-4">{{ $link->label }}</a>
            <span class="shrink-0 font-mono text-[10px] uppercase tracking-widest text-zinc-500">{{ $link->kind }}</span>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin enlaces.</li>
        @endforelse
      </ul>
    </section>

  </div>

@endsection
