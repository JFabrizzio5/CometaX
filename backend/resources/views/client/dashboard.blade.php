@extends('layouts.app')

@section('title', 'Mi panel')

@php
    $tipos = [
        'junta_mensual' => 'Junta mensual',
        'soporte_tecnico' => 'Soporte técnico',
        'consultoria_nuevo_proyecto' => 'Nuevo proyecto',
        'revision_contrato' => 'Revisión de contrato',
    ];
    $statusLabels = ['activo' => 'Activo', 'en_revision' => 'En revisión', 'finalizado' => 'Finalizado'];
    $hp = $stats['horas_plan'] > 0 ? min(100, round($stats['horas_consumidas'] / $stats['horas_plan'] * 100)) : 0;
@endphp

@section('content')
<div class="mx-auto max-w-6xl px-6 py-10">

  <header class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">{{ $client->name }}</p>
      <h1 class="mt-2 text-3xl font-semibold tracking-tight">Hola, {{ auth()->user()->name }}</h1>
      <p class="mt-1 text-sm text-zinc-400">Plan: <span class="text-white">{{ $client->plan?->name ?? 'Sin plan' }}</span></p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('billing.planes') }}" class="h-11 flex items-center rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">Cambiar plan</a>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="h-11 rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">Salir</button>
      </form>
    </div>
  </header>

  @if (session('status'))
    <div class="mt-8 rounded-control border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="mt-8 rounded-control border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
      <ul class="list-disc pl-4 space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- KPIs --}}
  <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Proyectos activos</p>
      <p class="mt-3 text-3xl font-semibold tracking-tight">{{ $stats['proyectos_activos'] }}</p>
    </div>
    <div class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Horas del plan</p>
      <p class="mt-3 text-3xl font-semibold tracking-tight">
        {{ rtrim(rtrim(number_format($stats['horas_consumidas'], 1), '0'), '.') }}<span class="text-lg text-zinc-500">/{{ $stats['horas_plan'] ?: '—' }}h</span>
      </p>
      @if ($stats['horas_plan'] > 0)
        <div class="mt-3 h-1.5 w-full rounded-full bg-white/10 overflow-hidden">
          <div class="h-full rounded-full {{ $hp >= 90 ? 'bg-amber-400' : 'bg-emerald-400' }}" style="width: {{ $hp }}%"></div>
        </div>
      @endif
    </div>
    <div class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Incidencias abiertas</p>
      <p class="mt-3 text-3xl font-semibold tracking-tight {{ $stats['incidencias_abiertas'] > 0 ? 'text-amber-300' : '' }}">{{ $stats['incidencias_abiertas'] }}</p>
    </div>
    <div class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Próxima cita</p>
      @if ($stats['proxima_cita'])
        <p class="mt-3 text-2xl font-semibold tracking-tight">{{ $stats['proxima_cita']->appointment_date->format('d M') }}</p>
        <p class="mt-1 font-mono text-xs text-zinc-500">{{ substr($stats['proxima_cita']->start_time, 0, 5) }} · {{ str_replace('_', ' ', $stats['proxima_cita']->meeting_type) }}</p>
      @else
        <p class="mt-3 text-sm text-zinc-500">Sin citas</p>
      @endif
    </div>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    {{-- Proyectos --}}
    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Tus proyectos</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($client->projects as $p)
          <li class="py-4">
            <div class="flex items-center justify-between gap-3">
              <p class="text-sm font-medium">{{ $p->name }}</p>
              <span class="font-mono text-[10px] uppercase tracking-widest text-zinc-400">{{ $statusLabels[$p->status] ?? $p->status }}</span>
            </div>
            <div class="mt-2 h-1.5 w-full rounded-full bg-white/10 overflow-hidden">
              <div class="h-full rounded-full bg-white/70" style="width: {{ (int) $p->progress_percent }}%"></div>
            </div>
            <p class="mt-1 font-mono text-xs text-zinc-500">{{ $p->progress_percent }}% · {{ $p->hours_used }}/{{ $p->hours_budgeted }}h</p>
          </li>
        @empty
          <li class="py-8 text-center text-sm text-zinc-500">Aún no hay proyectos. Agenda una reunión para arrancar.</li>
        @endforelse
      </ul>
    </section>

    {{-- Agendar reunión --}}
    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Agendar una reunión</h2>
      <form method="POST" action="{{ route('client.appointments.request') }}" class="mt-4 space-y-4">
        @csrf
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Tipo</label>
          <select name="meeting_type" required class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            @foreach ($tipos as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
          </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Fecha</label>
            <input type="date" name="appointment_date" required min="{{ now()->toDateString() }}" value="{{ old('appointment_date') }}" class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-3 py-3 text-sm outline-none focus:border-white/40 transition" />
          </div>
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Inicio</label>
            <input type="time" name="start_time" required value="{{ old('start_time') }}" class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-3 py-3 text-sm outline-none focus:border-white/40 transition" />
          </div>
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Fin</label>
            <input type="time" name="end_time" required value="{{ old('end_time') }}" class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-3 py-3 text-sm outline-none focus:border-white/40 transition" />
          </div>
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Nota (opcional)</label>
          <textarea name="notes" rows="2" class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" placeholder="¿De qué quieres hablar?">{{ old('notes') }}</textarea>
        </div>
        <button class="h-12 w-full rounded-control bg-white px-5 text-sm font-semibold text-black transition hover:bg-zinc-200">Solicitar reunión</button>
      </form>
    </section>

  </div>

  <div class="mt-6 grid gap-6 lg:grid-cols-3">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Próximas citas</h2>
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

</div>
@endsection
