@extends('layouts.client')

@section('title', 'Incidencias')

@php
    $columns = [
        'nuevo'    => 'Nuevo',
        'revision' => 'En revisión',
        'progreso' => 'En progreso',
        'resuelto' => 'Resuelto',
    ];
    $prioLabel = ['urgente' => 'Urgente', 'media' => 'Media', 'baja' => 'Baja'];
    $prioBadge = [
        'urgente' => 'text-red-300 bg-red-400/10 border border-red-400/20',
        'media'   => 'text-amber-300 bg-amber-400/10 border border-amber-400/20',
        'baja'    => 'text-zinc-300 bg-zinc-400/10 border border-zinc-400/20',
    ];
@endphp

@section('client-content')

  <div class="space-y-6">

    <div class="flex items-center justify-between gap-4">
      <div>
        <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Soporte técnico</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Incidencias</h1>
      </div>
      <button id="newIncidentBtn" class="flex items-center gap-2 rounded-full bg-white text-black font-semibold text-sm px-5 py-2.5 hover:bg-zinc-200 transition">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        Reportar incidencia
      </button>
    </div>

    <!-- Panel nueva incidencia -->
    <div id="newIncidentPanel" class="{{ $errors->any() ? '' : 'hidden ' }}rounded-card bg-white/[0.04] border border-white/15 p-6">
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-base font-semibold">Reportar nueva incidencia</h2>
        <button id="closeIncidentPanel" type="button" class="text-zinc-500 hover:text-white">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      <form method="POST" action="{{ route('client.incidents.store') }}" class="grid md:grid-cols-2 gap-4">
        @csrf
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Proyecto relacionado</label>
          <select name="project_id" class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 [&>option]:bg-zinc-900">
            @foreach ($projects as $project)
              <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>{{ $project->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Prioridad</label>
          <select name="priority" class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 [&>option]:bg-zinc-900">
            <option value="baja" @selected(old('priority') === 'baja')>Baja</option>
            <option value="media" @selected(old('priority', 'media') === 'media')>Media</option>
            <option value="urgente" @selected(old('priority') === 'urgente')>Urgente</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Título</label>
          <input type="text" name="title" value="{{ old('title') }}" placeholder="Ej. Error al cargar el catálogo de productos"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm placeholder:text-zinc-600 outline-none focus:border-white/40" />
        </div>
        <div class="md:col-span-2">
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Descripción</label>
          <textarea name="description" rows="3" placeholder="Describe el problema, cuándo ocurre y a quién afecta..."
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm placeholder:text-zinc-600 outline-none focus:border-white/40 resize-none">{{ old('description') }}</textarea>
        </div>
        <div class="md:col-span-2 flex items-center justify-end gap-3 pt-2">
          <button id="cancelIncidentBtn" type="button" class="rounded-full border border-white/15 text-sm font-medium px-5 py-2.5 hover:bg-white/5 transition">Cancelar</button>
          <button type="submit" class="rounded-full bg-white text-black text-sm font-semibold px-5 py-2.5 hover:bg-zinc-200 transition">Enviar incidencia</button>
        </div>
      </form>
    </div>

    <!-- Selector de proyecto -->
    <div>
      <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mb-3">Dividir por proyecto</p>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <button data-project-filter="all" class="text-left rounded-card border border-white bg-white/10 p-5 transition">
          <p class="text-sm font-semibold mb-1">Todos los proyectos</p>
          <p class="text-xs text-zinc-500">{{ $stats['abiertas'] }} incidencias abiertas</p>
        </button>
        @foreach ($projects as $project)
          @php
            $abiertas = $project->incidents->where('status', '!=', 'resuelto');
            $urgentes = $abiertas->where('priority', 'urgente')->count();
            $nAbiertas = $abiertas->count();
          @endphp
          <button data-project-filter="{{ $project->id }}" class="text-left rounded-card border border-white/10 hover:border-white/25 p-5 transition">
            <div class="flex items-center justify-between mb-2">
              <p class="text-sm font-semibold truncate pr-2">{{ $project->name }}</p>
              @if ($urgentes > 0)
                <span class="font-mono text-[10px] text-red-300 shrink-0">{{ $urgentes }} urgente{{ $urgentes == 1 ? '' : 's' }}</span>
              @else
                <span class="font-mono text-[10px] text-zinc-500 shrink-0">{{ $nAbiertas }} abierta{{ $nAbiertas == 1 ? '' : 's' }}</span>
              @endif
            </div>
            <div class="h-1.5 bg-white/10 rounded-full overflow-hidden mb-2"><div class="h-full bg-white rounded-full" style="width:{{ $project->progress_percent }}%"></div></div>
            <p class="text-xs text-zinc-500">{{ $project->progress_percent }}% completado</p>
          </button>
        @endforeach
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="rounded-card bg-white/[0.03] border border-white/10 p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Nuevas</p>
        <p class="text-2xl font-medium mt-2">{{ $stats['nuevas'] }}</p>
      </div>
      <div class="rounded-card bg-white/[0.03] border border-white/10 p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">En progreso</p>
        <p class="text-2xl font-medium mt-2">{{ $stats['progreso'] }}</p>
      </div>
      <div class="rounded-card bg-white/[0.03] border border-white/10 p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Resueltas (mes)</p>
        <p class="text-2xl font-medium mt-2">{{ $stats['resueltas_mes'] }}</p>
      </div>
      <div class="rounded-card bg-white/[0.03] border border-white/10 p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Abiertas</p>
        <p class="text-2xl font-medium mt-2">{{ $stats['abiertas'] }}</p>
      </div>
    </div>

    <!-- Kanban -->
    <div class="grid md:grid-cols-4 gap-4">
      @foreach ($columns as $key => $label)
        @php $items = $byStatus->get($key) ?? collect(); @endphp
        <div class="rounded-card bg-white/[0.02] border border-white/10 p-4">
          <div class="flex items-center justify-between mb-4 px-1">
            <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-400">{{ $label }}</p>
            <span class="font-mono text-[10px] text-zinc-500" data-column-count>{{ $items->count() }}</span>
          </div>
          <div class="space-y-3" data-column="{{ $key }}">
            @foreach ($items as $incident)
              @php
                $ini = '—';
                if ($incident->assignee) {
                    $words = preg_split('/\s+/', trim($incident->assignee->name));
                    $ini = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).mb_substr($words[1] ?? '', 0, 1));
                }
              @endphp
              <div class="rounded-control bg-white/[0.04] border border-white/10 p-4 hover:border-white/25 transition cursor-pointer{{ $key === 'resuelto' ? ' opacity-80' : '' }}" data-project="{{ $incident->project_id }}">
                <div class="flex items-center justify-between mb-2">
                  <span class="font-mono text-[10px] uppercase tracking-widest {{ $prioBadge[$incident->priority] ?? $prioBadge['baja'] }} rounded-full px-2 py-0.5">{{ $prioLabel[$incident->priority] ?? $incident->priority }}</span>
                  <span class="font-mono text-[10px] text-zinc-600">#{{ $incident->ticket_code }}</span>
                </div>
                <p class="text-sm font-medium">{{ $incident->title }}</p>
                <p class="text-xs text-zinc-500 mt-2">{{ $incident->project?->name }}</p>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-white/5">
                  <span class="text-[11px] text-zinc-500">{{ $incident->created_at->format('d M') }}</span>
                  <div class="h-6 w-6 rounded-full bg-white/10 flex items-center justify-center font-mono text-[9px]">{{ $ini }}</div>
                </div>
              </div>
            @endforeach
            <p class="{{ $items->count() ? 'hidden ' : '' }}text-xs text-zinc-600 text-center py-6" data-empty-state>Sin incidencias en este proyecto</p>
          </div>
        </div>
      @endforeach
    </div>

  </div>

  <script>
    (function () {
      const panel = document.getElementById('newIncidentPanel');
      document.getElementById('newIncidentBtn')?.addEventListener('click', () => panel.classList.toggle('hidden'));
      document.getElementById('closeIncidentPanel')?.addEventListener('click', () => panel.classList.add('hidden'));
      document.getElementById('cancelIncidentBtn')?.addEventListener('click', () => panel.classList.add('hidden'));

      const filters = document.querySelectorAll('[data-project-filter]');
      function setActive(btn) {
        filters.forEach((b) => {
          b.classList.remove('border-white', 'bg-white/10');
          b.classList.add('border-white/10', 'hover:border-white/25');
        });
        btn.classList.remove('border-white/10', 'hover:border-white/25');
        btn.classList.add('border-white', 'bg-white/10');
      }
      function applyFilter(pid) {
        document.querySelectorAll('[data-column]').forEach((col) => {
          let visible = 0;
          col.querySelectorAll('[data-project]').forEach((card) => {
            const show = pid === 'all' || card.dataset.project === pid;
            card.classList.toggle('hidden', !show);
            if (show) visible++;
          });
          const count = col.parentElement.querySelector('[data-column-count]');
          if (count) count.textContent = visible;
          const empty = col.querySelector('[data-empty-state]');
          if (empty) empty.classList.toggle('hidden', visible > 0);
        });
      }
      filters.forEach((btn) => btn.addEventListener('click', () => {
        setActive(btn);
        applyFilter(btn.dataset.projectFilter);
      }));
    })();
  </script>

@endsection
