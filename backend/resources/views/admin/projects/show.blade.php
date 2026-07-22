@extends('layouts.admin')

@section('title', $project->name)

@php
    $statusLabels = ['activo' => 'Activo', 'en_revision' => 'En revisión', 'finalizado' => 'Finalizado'];
    $milestoneLabels = ['pending' => 'Pendiente', 'in_progress' => 'En curso', 'done' => 'Hecho'];
    $milestoneBadge = [
        'pending' => 'border-zinc-500/30 bg-zinc-500/10 text-zinc-300',
        'in_progress' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'done' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
    ];
@endphp

@section('admin-content')

  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Proyecto</p>
      <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $project->name }}</h1>
      <p class="mt-1 font-mono text-xs text-zinc-500">
        {{ $project->client->name }} · {{ $statusLabels[$project->status] ?? $project->status }} · {{ $project->progress_percent }}% · {{ $project->hours_used }}/{{ $project->hours_budgeted }}h
      </p>
    </div>
    <a href="{{ route('admin.projects.index') }}"
       class="h-11 flex items-center rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
      ← Proyectos
    </a>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Actualizar proyecto</h2>

      <form method="POST" action="{{ route('admin.projects.update', $project) }}" class="mt-4 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Estado</label>
            <select name="status"
              class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
              @foreach ($statusLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $project->status) === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">% avance</label>
            <input type="number" name="progress_percent" value="{{ old('progress_percent', $project->progress_percent) }}" min="0" max="100" required
              class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Horas usadas</label>
            <input type="number" name="hours_used" value="{{ old('hours_used', $project->hours_used) }}" min="0" step="0.5" required
              class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
          </div>
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Horas presupuestadas</label>
            <input type="number" name="hours_budgeted" value="{{ old('hours_budgeted', $project->hours_budgeted) }}" min="0" step="0.5" required
              class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Entrega estimada</label>
            <input type="date" name="estimated_delivery" value="{{ old('estimated_delivery', $project->estimated_delivery?->format('Y-m-d')) }}"
              class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
          </div>
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Consultor líder</label>
            <select name="lead_consultant_id"
              class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
              <option value="">Sin asignar</option>
              @foreach ($consultants as $consultant)
                <option value="{{ $consultant->id }}" @selected(old('lead_consultant_id', $project->lead_consultant_id) == $consultant->id)>{{ $consultant->name }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="pt-1">
          <button class="h-12 rounded-control bg-white px-6 text-sm font-semibold text-black transition hover:bg-zinc-200">
            Guardar cambios
          </button>
        </div>
      </form>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Publicar avance</h2>

      <form method="POST" action="{{ route('admin.projects.activities.store', $project) }}" class="mt-4 space-y-3">
        @csrf
        <textarea name="description" rows="3" required placeholder="¿Qué se avanzó?"
          class="w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition">{{ old('description') }}</textarea>
        <button class="h-11 rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">
          Publicar avance
        </button>
      </form>

      <ul class="mt-5 border-t border-white/10 divide-y divide-white/5">
        @forelse ($project->activities as $activity)
          <li class="py-3">
            <p class="text-sm text-zinc-200">{{ $activity->description ?? $activity->action }}</p>
            <p class="mt-1 font-mono text-xs text-zinc-500">
              {{ $activity->occurred_at?->format('d/m H:i') }}@if ($activity->actor instanceof \App\Models\Consultant) · {{ $activity->actor->name }}@endif
            </p>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin actividad todavía.</li>
        @endforelse
      </ul>
    </section>

  </div>

  <div class="mt-6 grid gap-6 lg:grid-cols-2">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Hitos</h2>

      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($project->milestones as $milestone)
          <li class="py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
              <p class="truncate text-sm font-medium">{{ $milestone->label }}</p>
              <span class="inline-flex shrink-0 rounded-full border px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-widest {{ $milestoneBadge[$milestone->status] ?? $milestoneBadge['pending'] }}">
                {{ $milestoneLabels[$milestone->status] ?? $milestone->status }}
              </span>
            </div>
            <form method="POST" action="{{ route('admin.milestones.update', $milestone) }}" class="flex shrink-0 items-center gap-2">
              @csrf
              @method('PUT')
              <select name="status"
                class="h-9 rounded-control bg-white/5 border border-white/15 px-3 text-xs outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
                @foreach ($milestoneLabels as $value => $label)
                  <option value="{{ $value }}" @selected($milestone->status === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <button class="h-9 rounded-control border border-white/15 px-3 font-mono text-[10px] uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
                OK
              </button>
            </form>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin hitos todavía.</li>
        @endforelse
      </ul>

      <form method="POST" action="{{ route('admin.projects.milestones.store', $project) }}" class="mt-5 flex gap-2 border-t border-white/10 pt-5">
        @csrf
        <input type="text" name="label" placeholder="Nuevo hito…" required
          class="h-11 w-full rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition" />
        <button class="h-11 shrink-0 rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
          + Hito
        </button>
      </form>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <div class="flex items-center justify-between gap-4">
        <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Incidencias del proyecto</h2>
        <a href="{{ route('admin.incidents.create', ['project' => $project->id]) }}"
           class="font-mono text-[10px] uppercase tracking-widest text-zinc-300 underline-offset-4 transition hover:text-white hover:underline">
          Nueva incidencia
        </a>
      </div>

      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($project->incidents as $incident)
          <li class="py-3 flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-medium">
                <span class="font-mono text-xs text-zinc-500">{{ $incident->ticket_code }}</span>
                {{ $incident->title }}
              </p>
              <p class="font-mono text-xs text-zinc-500">{{ $incident->priority }} · {{ $incident->status }}</p>
            </div>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin incidencias todavía.</li>
        @endforelse
      </ul>
    </section>

  </div>

@endsection
