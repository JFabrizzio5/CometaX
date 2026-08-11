@extends('layouts.admin')

@section('title', 'Incidencias')

@php
    $cols = ['nuevo' => 'Nuevo', 'revision' => 'En revisión', 'progreso' => 'En progreso', 'resuelto' => 'Resuelto'];
    $prBadge = ['urgente' => 'text-red-300 bg-red-400/10 border-red-400/20', 'media' => 'text-amber-300 bg-amber-400/10 border-amber-400/20', 'baja' => 'text-zinc-300 bg-white/5 border-white/15'];
    $initials = function ($name) {
        $p = preg_split('/\s+/', trim((string) $name));
        return strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[1] ?? '', 0, 1)) ?: '—';
    };
    $openByProject = $incidents->where('status', '!=', 'resuelto')->groupBy('project_id');
@endphp

@section('admin-content')

  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Incidencias</h1>
      <p class="mt-1 text-sm text-zinc-400">Tablero de todos los clientes. Cambia el estado con el selector de cada tarjeta.</p>
    </div>
    <a href="{{ route('admin.incidents.create') }}" class="h-11 flex items-center rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">+ Nueva incidencia</a>
  </div>

  {{-- Dividir por proyecto --}}
  <div class="mt-8">
    <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mb-3">Dividir por proyecto</p>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <button type="button" data-project-filter="all" class="proj-filter text-left rounded-card border border-white bg-white/10 p-5 transition">
        <p class="text-sm font-semibold mb-1">Todos los proyectos</p>
        <p class="text-xs text-zinc-500">{{ $stats['abiertas'] }} abiertas</p>
      </button>
      @foreach ($projects as $p)
        @php $op = $openByProject->get($p->id, collect()); $urg = $op->where('priority', 'urgente')->count(); @endphp
        <button type="button" data-project-filter="{{ $p->id }}" class="proj-filter text-left rounded-card border border-white/10 hover:border-white/25 p-5 transition">
          <div class="flex items-center justify-between mb-2 gap-2">
            <p class="text-sm font-semibold truncate">{{ $p->name }}</p>
            <span class="font-mono text-[10px] shrink-0 {{ $urg ? 'text-red-300' : 'text-zinc-500' }}">
              {{ $urg ? $urg.' urgente'.($urg === 1 ? '' : 's') : $op->count().' abierta'.($op->count() === 1 ? '' : 's') }}
            </span>
          </div>
          <p class="text-xs text-zinc-500 truncate">{{ $p->client?->name }}</p>
        </button>
      @endforeach
    </div>
  </div>

  {{-- KPIs --}}
  <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
    @foreach (['nuevas' => ['Nuevas', $stats['nuevas']], 'progreso' => ['En progreso', $stats['progreso']], 'resueltas_mes' => ['Resueltas (mes)', $stats['resueltas_mes']], 'abiertas' => ['Abiertas', $stats['abiertas']]] as $kpi => [$label, $val])
      <div class="rounded-card border border-white/10 bg-white/[0.03] p-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{{ $label }}</p>
        {{-- data-kpi: el filtro por proyecto recalcula estos números en el cliente. --}}
        <p class="mt-2 text-2xl font-medium" data-kpi="{{ $kpi }}">{{ $val }}</p>
      </div>
    @endforeach
  </div>

  {{-- Kanban --}}
  <div class="mt-6 grid md:grid-cols-4 gap-4">
    @foreach ($cols as $key => $title)
      @php $items = $byStatus->get($key, collect()); @endphp
      <div class="rounded-card border border-white/10 bg-white/[0.02] p-4">
        <div class="flex items-center justify-between mb-4 px-1">
          <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-400">{{ $title }}</p>
          <span class="font-mono text-[10px] text-zinc-500" data-col-count="{{ $key }}">{{ $items->count() }}</span>
        </div>
        <div class="space-y-3">
          {{-- Siempre en el DOM: al filtrar por proyecto no hay render nuevo del servidor. --}}
          <p class="{{ $items->isEmpty() ? '' : 'hidden' }} text-xs text-zinc-600 text-center py-6" data-col-empty="{{ $key }}">Sin incidencias</p>
          @foreach ($items as $inc)
            <div data-project="{{ $inc->project_id }}"
                 data-status="{{ $inc->status }}"
                 data-resuelta-mes="{{ $inc->resolved_at && $inc->resolved_at->isSameMonth(now()) ? '1' : '0' }}"
                 class="incident-card rounded-control bg-white/[0.04] border border-white/10 p-4 transition hover:border-white/25">
              <div class="flex items-center justify-between mb-2">
                <span class="font-mono text-[10px] uppercase tracking-widest rounded-full border px-2 py-0.5 {{ $prBadge[$inc->priority] ?? $prBadge['baja'] }}">{{ $inc->priority }}</span>
                <span class="font-mono text-[10px] text-zinc-600">{{ $inc->ticket_code }}</span>
              </div>
              <p class="text-sm font-medium">{{ $inc->title }}</p>
              <p class="text-xs text-zinc-500 mt-2">{{ $inc->project?->name }} · {{ $inc->project?->client?->name }}</p>
              <div class="flex items-center justify-between mt-3 pt-3 border-t border-white/5 gap-2">
                <form method="POST" action="{{ route('admin.incidents.move', $inc) }}">
                  @csrf
                  <select name="status" onchange="this.form.submit()" class="h-8 rounded-control bg-white/5 border border-white/15 px-2 text-[11px] outline-none focus:border-white/40 [&>option]:bg-zinc-900">
                    @foreach ($cols as $sk => $sl)<option value="{{ $sk }}" @selected($inc->status === $sk)>{{ $sl }}</option>@endforeach
                  </select>
                </form>
                <div class="flex items-center gap-2 shrink-0">
                  <a href="{{ route('admin.incidents.edit', $inc) }}" class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-white">Editar</a>
                  <div class="h-6 w-6 rounded-full bg-white/10 flex items-center justify-center font-mono text-[9px]" title="{{ $inc->assignee?->name }}">{{ $initials($inc->assignee?->name) }}</div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>

  <script>
    (function () {
      var buttons = document.querySelectorAll('.proj-filter');
      var cards = Array.prototype.slice.call(document.querySelectorAll('.incident-card'));

      // Los contadores vienen del servidor sobre TODOS los proyectos. Al filtrar
      // en el cliente hay que recalcularlos o el tablero muestra números que no
      // corresponden a lo que se está viendo (y columnas vacías con conteo > 0).
      function pintar(filtro) {
        var visibles = [];

        cards.forEach(function (c) {
          var visible = filtro === 'all' || c.getAttribute('data-project') === filtro;
          c.style.display = visible ? '' : 'none';
          if (visible) { visibles.push(c); }
        });

        var porEstado = {};
        visibles.forEach(function (c) {
          var e = c.getAttribute('data-status');
          porEstado[e] = (porEstado[e] || 0) + 1;
        });

        document.querySelectorAll('[data-col-count]').forEach(function (el) {
          el.textContent = porEstado[el.getAttribute('data-col-count')] || 0;
        });

        document.querySelectorAll('[data-col-empty]').forEach(function (el) {
          el.classList.toggle('hidden', (porEstado[el.getAttribute('data-col-empty')] || 0) > 0);
        });

        var kpis = {
          nuevas: porEstado['nuevo'] || 0,
          progreso: (porEstado['revision'] || 0) + (porEstado['progreso'] || 0),
          resueltas_mes: visibles.filter(function (c) { return c.getAttribute('data-resuelta-mes') === '1'; }).length,
          abiertas: visibles.filter(function (c) { return c.getAttribute('data-status') !== 'resuelto'; }).length
        };

        document.querySelectorAll('[data-kpi]').forEach(function (el) {
          el.textContent = kpis[el.getAttribute('data-kpi')];
        });
      }

      buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
          buttons.forEach(function (b) { b.classList.remove('border-white', 'bg-white/10'); b.classList.add('border-white/10'); });
          btn.classList.add('border-white', 'bg-white/10'); btn.classList.remove('border-white/10');
          pintar(btn.getAttribute('data-project-filter'));
        });
      });
    })();
  </script>

@endsection
