@extends('layouts.admin')

@section('title', 'Nuevo proyecto')

@section('admin-content')

  <div class="max-w-2xl">
    <h1 class="text-2xl font-semibold tracking-tight">Nuevo proyecto</h1>

    <form method="POST" action="{{ route('admin.projects.store') }}"
          class="mt-8 rounded-card border border-white/10 bg-white/[0.03] p-8 space-y-5">
      @csrf

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Cliente *</label>
        <select name="client_id" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
          <option value="">Selecciona un cliente</option>
          @foreach ($clients as $client)
            <option value="{{ $client->id }}" @selected(old('client_id', $selectedClient) == $client->id)>{{ $client->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Nombre *</label>
        <input type="text" name="name" value="{{ old('name') }}" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Descripción</label>
        <textarea name="description" rows="3"
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition">{{ old('description') }}</textarea>
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Consultor líder</label>
          <select name="lead_consultant_id"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            <option value="">Sin asignar</option>
            @foreach ($consultants as $consultant)
              <option value="{{ $consultant->id }}" @selected(old('lead_consultant_id') == $consultant->id)>{{ $consultant->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Estado *</label>
          <select name="status" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            @foreach (['activo' => 'Activo', 'en_revision' => 'En revisión', 'finalizado' => 'Finalizado'] as $value => $label)
              <option value="{{ $value }}" @selected(old('status', 'activo') === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Fecha de inicio</label>
          <input type="date" name="start_date" value="{{ old('start_date') }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Entrega estimada</label>
          <input type="date" name="estimated_delivery" value="{{ old('estimated_delivery') }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
      </div>

      <div class="grid gap-5 sm:grid-cols-3">
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">% avance *</label>
          <input type="number" name="progress_percent" value="{{ old('progress_percent', 0) }}" min="0" max="100" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Horas presupuestadas *</label>
          <input type="number" name="hours_budgeted" value="{{ old('hours_budgeted', 0) }}" min="0" step="0.5" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Horas usadas *</label>
          <input type="number" name="hours_used" value="{{ old('hours_used', 0) }}" min="0" step="0.5" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button class="h-12 rounded-control bg-white px-6 text-sm font-semibold text-black transition hover:bg-zinc-200">
          Crear proyecto
        </button>
        <a href="{{ route('admin.projects.index') }}"
           class="h-12 flex items-center rounded-control border border-white/15 px-5 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
          Cancelar
        </a>
      </div>
    </form>
  </div>

@endsection
