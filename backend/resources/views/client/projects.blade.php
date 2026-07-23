@extends('layouts.client')

@section('title', 'Proyectos')

@php
    $statusLabel = [
        'activo' => 'Activo',
        'en_revision' => 'En revisión',
        'finalizado' => 'Finalizado',
    ];
    $statusBadge = [
        'activo' => 'text-emerald-300 bg-emerald-400/10 border border-emerald-400/20',
        'en_revision' => 'text-amber-300 bg-amber-400/10 border border-amber-400/20',
        'finalizado' => 'text-zinc-400 bg-zinc-500/10 border border-zinc-500/20',
    ];
    $counts = [
        'todos' => $projects->count(),
        'activo' => $projects->where('status', 'activo')->count(),
        'en_revision' => $projects->where('status', 'en_revision')->count(),
        'finalizado' => $projects->where('status', 'finalizado')->count(),
    ];
@endphp

@section('client-content')

  <div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-3xl font-semibold tracking-tight">Proyectos</h1>
    <a href="{{ route('client.support') }}" class="hidden sm:flex items-center gap-2 rounded-full bg-white text-black font-semibold text-sm px-5 py-2.5 hover:bg-zinc-200 transition">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
      Cotizar
    </a>
  </div>

  <div class="mt-8 space-y-6">

    <!-- Filtros -->
    <div class="flex flex-wrap items-center gap-2">
      <button data-filter="todos" class="rounded-full bg-white text-black text-xs font-mono uppercase tracking-widest px-4 py-2">Todos ({{ $counts['todos'] }})</button>
      <button data-filter="activo" class="rounded-full border border-white/15 text-zinc-400 hover:text-white hover:border-white/30 text-xs font-mono uppercase tracking-widest px-4 py-2 transition">Activos ({{ $counts['activo'] }})</button>
      <button data-filter="en_revision" class="rounded-full border border-white/15 text-zinc-400 hover:text-white hover:border-white/30 text-xs font-mono uppercase tracking-widest px-4 py-2 transition">En revisión ({{ $counts['en_revision'] }})</button>
      <button data-filter="finalizado" class="rounded-full border border-white/15 text-zinc-400 hover:text-white hover:border-white/30 text-xs font-mono uppercase tracking-widest px-4 py-2 transition">Finalizados ({{ $counts['finalizado'] }})</button>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">

      @forelse ($projects as $project)
        @php
            $open = $project->incidents->where('status', '!=', 'resuelto');
            $urgent = $open->where('priority', 'urgente')->count();
            $openCount = $open->count();
        @endphp
        <a href="{{ route('client.projects.show', $project) }}" data-status="{{ $project->status }}" class="rounded-card bg-white/[0.03] border border-white/10 p-6 hover:border-white/25 hover:bg-white/[0.045] transition flex flex-col">
          <div class="flex items-start justify-between mb-4">
            <div class="h-11 w-11 rounded-control bg-white/5 border border-white/10 flex items-center justify-center">
              <svg class="h-5 w-5 text-zinc-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c0-.399.169-.78.465-1.05L11.318 2.1a1.5 1.5 0 011.965 0l7.103 6.626c.296.27.464.651.464 1.05v9.474a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V9.776z" /></svg>
            </div>
            <span class="font-mono text-[10px] uppercase tracking-widest rounded-full px-2.5 py-1 {{ $statusBadge[$project->status] ?? $statusBadge['finalizado'] }}">{{ $statusLabel[$project->status] ?? $project->status }}</span>
          </div>
          <p class="text-base font-semibold mb-1">{{ $project->name }}</p>
          <p class="text-xs text-zinc-500 mb-5">{{ $project->description }}@if ($project->start_date) · Inició {{ $project->start_date->format('d M Y') }}@endif</p>
          <div class="mt-auto">
            <div class="h-1.5 bg-white/10 rounded-full overflow-hidden mb-2"><div class="h-full bg-white rounded-full" style="width:{{ (int) $project->progress_percent }}%"></div></div>
            <div class="flex items-center justify-between text-xs text-zinc-500">
              <span>{{ (int) $project->progress_percent }}% completado</span><span>{{ $project->hours_used }}h / {{ $project->hours_budgeted }}h</span>
            </div>
            @if ($urgent > 0)
              <div class="flex items-center gap-1.5 text-xs text-red-300 mt-2 pt-2 border-t border-white/5">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                {{ $urgent }} incidencia{{ $urgent === 1 ? '' : 's' }} urgente{{ $urgent === 1 ? '' : 's' }}
              </div>
            @elseif ($openCount > 0)
              <div class="flex items-center gap-1.5 text-xs text-amber-300 mt-2 pt-2 border-t border-white/5">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                {{ $openCount }} incidencia{{ $openCount === 1 ? '' : 's' }} abierta{{ $openCount === 1 ? '' : 's' }}
              </div>
            @else
              <div class="flex items-center gap-1.5 text-xs text-zinc-500 mt-2 pt-2 border-t border-white/5">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                Sin incidencias abiertas
              </div>
            @endif
          </div>
        </a>
      @empty
        <div class="md:col-span-2 xl:col-span-3 rounded-card border border-white/10 bg-white/[0.03] p-12 text-center text-sm text-zinc-500">
          Aún no tienes proyectos. Cotiza uno nuevo para empezar.
        </div>
      @endforelse

      <!-- Card nuevo proyecto -->
      <a href="{{ route('client.support') }}" class="rounded-card border border-dashed border-white/15 p-6 hover:border-white/30 hover:bg-white/[0.02] transition flex flex-col items-center justify-center text-center gap-3 min-h-[220px]">
        <div class="h-11 w-11 rounded-control bg-white/5 border border-white/10 flex items-center justify-center">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        </div>
        <div>
          <p class="text-sm font-medium">Cotizar nuevo proyecto</p>
          <p class="text-xs text-zinc-500 mt-1">Cuéntanos qué necesitas y te enviamos una propuesta</p>
        </div>
      </a>
    </div>

  </div>

  <script>
    (function () {
      const buttons = document.querySelectorAll('[data-filter]');
      const cards = document.querySelectorAll('[data-status]');
      const activeCls = ['bg-white', 'text-black'];
      const inactiveCls = ['border', 'border-white/15', 'text-zinc-400', 'hover:text-white', 'hover:border-white/30', 'transition'];
      buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
          buttons.forEach((b) => {
            if (b === btn) { b.classList.add(...activeCls); b.classList.remove(...inactiveCls); }
            else { b.classList.remove(...activeCls); b.classList.add(...inactiveCls); }
          });
          const f = btn.dataset.filter;
          cards.forEach((c) => {
            c.style.display = (f === 'todos' || c.dataset.status === f) ? '' : 'none';
          });
        });
      });
    })();
  </script>

@endsection
