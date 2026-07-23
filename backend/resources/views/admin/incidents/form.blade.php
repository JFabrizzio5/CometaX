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

    @if ($incident->exists)
      <div class="mt-6 rounded-card border border-white/10 bg-white/[0.03] p-6">
        <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Evidencia</h2>

        <div class="mt-4 flex flex-wrap gap-3">
          @forelse ($incident->attachments as $att)
            <div class="relative group">
              @if ($att->kind === 'image')
                <a href="{{ $att->url }}" target="_blank" rel="noopener"><img src="{{ $att->url }}" alt="evidencia" class="h-20 w-20 rounded-lg object-cover border border-white/10" loading="lazy"></a>
              @else
                <a href="{{ $att->url }}" target="_blank" rel="noopener" class="inline-flex h-20 items-center gap-1 rounded-lg border border-white/15 px-3 text-sm text-sky-300 hover:border-white/30">🔗 {{ $att->label }}</a>
              @endif
              <span class="absolute -top-1 -left-1 rounded-full bg-black/70 px-1.5 py-0.5 font-mono text-[8px] uppercase text-zinc-400">{{ $att->source }}</span>
              <form method="POST" action="{{ route('admin.incidents.attachments.destroy', $att) }}" class="absolute -top-1 -right-1"
                    onsubmit="return confirm('¿Eliminar esta evidencia?')">
                @csrf @method('DELETE')
                <button class="h-5 w-5 rounded-full bg-black/70 text-red-300 text-xs leading-none hover:bg-red-500/30">&times;</button>
              </form>
            </div>
          @empty
            <p class="text-sm text-zinc-500">Sin evidencia todavía.</p>
          @endforelse
        </div>

        <form method="POST" action="{{ route('admin.incidents.attachments.store', $incident) }}" enctype="multipart/form-data" class="mt-5 border-t border-white/10 pt-5 grid gap-3 sm:grid-cols-2">
          @csrf
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Imagen</label>
            <input type="file" name="image" accept="image/*" class="mt-2 w-full text-sm text-zinc-400 file:mr-3 file:rounded-full file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-xs file:text-white hover:file:bg-white/20">
          </div>
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Enlace (Drive, video…)</label>
            <input type="url" name="link" placeholder="https://drive.google.com/…" class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40">
          </div>
          <div class="sm:col-span-2 flex items-center gap-3">
            <input type="text" name="label" placeholder="Etiqueta (opcional)" class="h-11 flex-1 rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40">
            <button class="h-11 rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">Agregar evidencia</button>
          </div>
        </form>
      </div>
    @endif
  </div>

@endsection
