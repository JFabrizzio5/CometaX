@extends('layouts.admin')

@section('title', $consultant->name)

@section('admin-content')

  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Consultor</p>
      <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $consultant->name }}</h1>
      <p class="mt-1 font-mono text-xs text-zinc-500">
        {{ $consultant->email ?? 'sin correo' }} · {{ $consultant->title ?? 'sin título' }} · {{ $consultant->role }}
      </p>
    </div>
    <div class="flex flex-wrap gap-2">
      <form method="POST" action="{{ route('admin.consultants.invite', $consultant) }}">
        @csrf
        <button class="h-11 flex items-center rounded-control border border-sky-500/40 bg-sky-500/10 px-4 font-mono text-xs uppercase tracking-widest text-sky-200 transition hover:bg-sky-500/20">
          {{ $consultant->password ? 'Reenviar acceso' : 'Enviar acceso' }}
        </button>
      </form>
      <a href="{{ route('admin.consultants.edit', $consultant) }}"
         class="h-11 flex items-center rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
        Editar
      </a>
    </div>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Proyectos asignados</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($consultant->projects as $project)
          <li class="py-3 flex items-center justify-between gap-4">
            <div>
              <a href="{{ route('admin.projects.show', $project) }}" class="text-sm font-medium hover:underline underline-offset-4">{{ $project->name }}</a>
              <p class="font-mono text-xs text-zinc-500">
                {{ $project->client?->name }}
                @if ($project->pivot->role_label) · {{ $project->pivot->role_label }} @endif
              </p>
            </div>
            <span class="font-mono text-xs text-zinc-400">{{ $project->status }}</span>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin proyectos asignados. Asígnale desde el detalle de un proyecto.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Incidencias asignadas</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($consultant->assignedIncidents as $incident)
          <li class="py-3 flex items-center justify-between gap-4">
            <div>
              <p class="text-sm">{{ $incident->title }}</p>
              <p class="font-mono text-xs text-zinc-500">{{ $incident->ticket_code }} · {{ $incident->priority }}</p>
            </div>
            <span class="font-mono text-xs text-zinc-400">{{ $incident->status }}</span>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin incidencias asignadas.</li>
        @endforelse
      </ul>
    </section>

  </div>

@endsection
