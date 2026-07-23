@extends('layouts.client')

@section('title', 'Mi panel')

@php
    $statusLabels = ['activo' => 'Activo', 'en_revision' => 'En revisión', 'finalizado' => 'Finalizado'];
    $hp = $stats['horas_plan'] > 0 ? min(100, round($stats['horas_consumidas'] / $stats['horas_plan'] * 100)) : 0;
    $hc = rtrim(rtrim(number_format($stats['horas_consumidas'], 1), '0'), '.');
@endphp

@section('client-content')

  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">{{ $client->name }}</p>
      <h1 class="mt-2 text-3xl font-semibold tracking-tight">Hola, {{ auth()->user()->name }}</h1>
      <p class="mt-1 text-sm text-zinc-400">Plan: <span class="text-white">{{ $client->plan?->name ?? 'Sin plan' }}</span></p>
    </div>
    <a href="{{ route('billing.planes') }}" class="h-11 flex items-center rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">Cambiar plan</a>
  </div>

  <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <a href="{{ route('client.projects') }}" class="rounded-card border border-white/10 bg-white/[0.03] p-6 transition hover:border-white/25">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Proyectos activos</p>
      <p class="mt-3 text-3xl font-semibold tracking-tight">{{ $stats['proyectos_activos'] }}</p>
    </a>
    <div class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Horas del plan</p>
      <p class="mt-3 text-3xl font-semibold tracking-tight">{{ $hc }}<span class="text-lg text-zinc-500">/{{ $stats['horas_plan'] ?: '—' }}h</span></p>
      @if ($stats['horas_plan'] > 0)
        <div class="mt-3 h-1.5 w-full rounded-full bg-white/10 overflow-hidden"><div class="h-full rounded-full {{ $hp >= 90 ? 'bg-amber-400' : 'bg-emerald-400' }}" style="width: {{ $hp }}%"></div></div>
      @endif
    </div>
    <a href="{{ route('client.incidents') }}" class="rounded-card border border-white/10 bg-white/[0.03] p-6 transition hover:border-white/25">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Incidencias abiertas</p>
      <p class="mt-3 text-3xl font-semibold tracking-tight {{ $stats['incidencias_abiertas'] > 0 ? 'text-amber-300' : '' }}">{{ $stats['incidencias_abiertas'] }}</p>
    </a>
    <a href="{{ route('client.calendar') }}" class="rounded-card border border-white/10 bg-white/[0.03] p-6 transition hover:border-white/25">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Próxima cita</p>
      @if ($stats['proxima_cita'])
        <p class="mt-3 text-2xl font-semibold tracking-tight">{{ $stats['proxima_cita']->appointment_date->format('d M') }}</p>
        <p class="mt-1 font-mono text-xs text-zinc-500">{{ substr($stats['proxima_cita']->start_time, 0, 5) }} · {{ str_replace('_', ' ', $stats['proxima_cita']->meeting_type) }}</p>
      @else
        <p class="mt-3 text-sm text-zinc-500">Sin citas</p>
      @endif
    </a>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <div class="flex items-center justify-between">
        <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Tus proyectos</h2>
        <a href="{{ route('client.projects') }}" class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-white transition">Ver todos →</a>
      </div>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($client->projects->take(4) as $p)
          <li class="py-4">
            <div class="flex items-center justify-between gap-3">
              <a href="{{ route('client.projects.show', $p) }}" class="text-sm font-medium hover:underline underline-offset-4">{{ $p->name }}</a>
              <span class="font-mono text-[10px] uppercase tracking-widest text-zinc-400">{{ $statusLabels[$p->status] ?? $p->status }}</span>
            </div>
            <div class="mt-2 h-1.5 w-full rounded-full bg-white/10 overflow-hidden"><div class="h-full rounded-full bg-white/70" style="width: {{ (int) $p->progress_percent }}%"></div></div>
            <p class="mt-1 font-mono text-xs text-zinc-500">{{ $p->progress_percent }}% · {{ $p->hours_used }}/{{ $p->hours_budgeted }}h</p>
          </li>
        @empty
          <li class="py-8 text-center text-sm text-zinc-500">Aún no hay proyectos.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Actividad reciente</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($actividad as $act)
          <li class="py-3">
            <p class="text-sm text-zinc-200">{{ $act->description ?? $act->action }}</p>
            <p class="mt-1 font-mono text-xs text-zinc-500">{{ $act->project?->name }} · {{ $act->occurred_at?->format('d/m H:i') }}</p>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin actividad todavía.</li>
        @endforelse
      </ul>
    </section>

  </div>

  <div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <div class="flex items-center justify-between">
        <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Próximas citas</h2>
        <a href="{{ route('client.calendar') }}" class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-white transition">Calendario →</a>
      </div>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($proximasCitas as $c)
          <li class="py-3 flex items-center justify-between gap-3">
            <div>
              <p class="text-sm">{{ str_replace('_', ' ', $c->meeting_type) }}</p>
              <p class="font-mono text-xs text-zinc-500">{{ $c->appointment_date->format('d/m') }} · {{ substr($c->start_time, 0, 5) }}</p>
            </div>
            <span class="font-mono text-[10px] uppercase tracking-widest {{ $c->status === 'confirmada' ? 'text-emerald-300' : ($c->status === 'solicitada' ? 'text-sky-300' : 'text-zinc-400') }}">{{ $c->status }}</span>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin citas próximas.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Avisos</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($avisos as $a)
          <li class="py-3">
            <p class="text-sm font-medium">{{ $a->title }}</p>
            <p class="mt-1 text-sm text-zinc-400">{{ $a->body }}</p>
            <p class="mt-1 font-mono text-xs text-zinc-600">{{ $a->published_at?->format('d/m/Y') }}</p>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin avisos.</li>
        @endforelse
      </ul>
    </section>
  </div>

@endsection
