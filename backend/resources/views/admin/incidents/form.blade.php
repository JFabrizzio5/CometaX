@extends('layouts.admin')

@section('title', $incident->exists ? 'Editar incidencia' : 'Nueva incidencia')

@section('admin-content')

  <div class="max-w-2xl">
    <h1 class="text-2xl font-semibold tracking-tight">
      {{ $incident->exists ? "Editar «{$incident->ticket_code}»" : 'Nueva incidencia' }}
    </h1>

    <form method="POST"
          action="{{ $incident->exists ? route('admin.incidents.update', $incident) : route('admin.incidents.store') }}"
          class="mt-8 rounded-card border border-white/10 bg-white/[0.03] p-8 space-y-5">
      @csrf
      @if ($incident->exists) @method('PUT') @endif

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Proyecto *</label>
        <select name="project_id" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
          <option value="">Selecciona un proyecto</option>
          @foreach ($projects as $project)
            <option value="{{ $project->id }}" @selected(old('project_id', $incident->project_id) == $project->id)>
              {{ $project->name }} — {{ $project->client?->name ?? 'sin cliente' }}
            </option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Título *</label>
        <input type="text" name="title" value="{{ old('title', $incident->title) }}" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Descripción</label>
        <textarea name="description" rows="4"
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition">{{ old('description', $incident->description) }}</textarea>
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Prioridad *</label>
          <select name="priority" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            <option value="baja" @selected(old('priority', $incident->priority) === 'baja')>Baja</option>
            <option value="media" @selected(old('priority', $incident->priority) === 'media')>Media</option>
            <option value="urgente" @selected(old('priority', $incident->priority) === 'urgente')>Urgente</option>
          </select>
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Estado *</label>
          <select name="status" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            <option value="nuevo" @selected(old('status', $incident->status) === 'nuevo')>Nuevo</option>
            <option value="revision" @selected(old('status', $incident->status) === 'revision')>En revisión</option>
            <option value="progreso" @selected(old('status', $incident->status) === 'progreso')>En progreso</option>
            <option value="resuelto" @selected(old('status', $incident->status) === 'resuelto')>Resuelto</option>
          </select>
        </div>
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Asignado a</label>
        <select name="assignee_consultant_id"
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
          <option value="">Sin asignar</option>
          @foreach ($consultants as $consultant)
            <option value="{{ $consultant->id }}" @selected(old('assignee_consultant_id', $incident->assignee_consultant_id) == $consultant->id)>
              {{ $consultant->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button class="h-12 rounded-control bg-white px-6 text-sm font-semibold text-black transition hover:bg-zinc-200">
          {{ $incident->exists ? 'Guardar cambios' : 'Crear incidencia' }}
        </button>
        <a href="{{ route('admin.incidents.index') }}"
           class="h-12 flex items-center rounded-control border border-white/15 px-5 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
          Cancelar
        </a>
      </div>
    </form>
  </div>

@endsection
