@extends('layouts.admin')

@section('title', 'Resumen')

@php
    $money = fn (int $cents) => '$' . number_format($cents / 100, 2);
@endphp

@section('admin-content')

  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([
      ['MRR', $money($stats['mrr_cents']), 'text-white'],
      ['Cobrado este mes', $money($stats['cobrado_mes_cents']), 'text-emerald-300'],
      ['Cobrado histórico', $money($stats['cobrado_total_cents']), 'text-zinc-300'],
      ['Clientes (pagando)', $stats['clientes'].' ('.$stats['pagando'].')', 'text-white'],
    ] as [$label, $value, $tone])
      <div class="rounded-card border border-white/10 bg-white/[0.03] p-6">
        <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">{{ $label }}</p>
        <p class="mt-3 text-2xl font-semibold tracking-tight {{ $tone }}">{{ $value }}</p>
      </div>
    @endforeach
  </div>

  <div class="mt-4 grid gap-4 sm:grid-cols-2">
    <a href="{{ route('admin.projects.index') }}" class="rounded-card border border-white/10 bg-white/[0.03] p-6 transition hover:border-white/25">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Proyectos en curso</p>
      <p class="mt-3 text-2xl font-semibold tracking-tight">{{ $stats['proyectos_activos'] }}</p>
    </a>
    <a href="{{ route('admin.incidents.index') }}" class="rounded-card border border-white/10 bg-white/[0.03] p-6 transition hover:border-white/25">
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Incidencias abiertas</p>
      <p class="mt-3 text-2xl font-semibold tracking-tight {{ $stats['incidencias_abiertas'] > 0 ? 'text-amber-300' : '' }}">{{ $stats['incidencias_abiertas'] }}</p>
    </a>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <div class="flex items-center justify-between">
        <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Próximas citas</h2>
        <a href="{{ route('admin.appointments.index') }}" class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-white transition">Ver todas →</a>
      </div>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($proximasCitas as $cita)
          <li class="py-3 flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-medium">{{ $cita->client?->name ?? $cita->lead?->name ?? 'Prospecto' }}</p>
              <p class="font-mono text-xs text-zinc-500">{{ str_replace('_', ' ', $cita->meeting_type) }}</p>
            </div>
            <p class="font-mono text-xs text-zinc-400 whitespace-nowrap">
              {{ $cita->appointment_date->format('d/m') }} · {{ substr($cita->start_time, 0, 5) }}
            </p>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin citas próximas.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <div class="flex items-center justify-between">
        <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Últimos avances publicados</h2>
        <a href="{{ route('admin.projects.index') }}" class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-white transition">Proyectos →</a>
      </div>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($ultimosAvances as $avance)
          <li class="py-3">
            <p class="text-sm">{{ $avance->description ?? $avance->action }}</p>
            <p class="mt-1 font-mono text-xs text-zinc-500">
              {{ $avance->project?->name }} · {{ $avance->project?->client?->name }} · {{ $avance->occurred_at->format('d/m H:i') }}
            </p>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Aún no hay avances publicados.</li>
        @endforelse
      </ul>
    </section>

  </div>

  <section class="mt-6 rounded-card border border-white/10 bg-white/[0.03] p-6">
    <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Cobros recientes</h2>
    <ul class="mt-4 divide-y divide-white/5">
      @forelse ($ultimosCobros as $cobro)
        <li class="py-3 flex items-center justify-between gap-4">
          <div>
            <p class="text-sm font-medium">{{ $cobro->concept }}</p>
            <p class="font-mono text-xs text-zinc-500">{{ $cobro->client?->name }} · {{ $cobro->invoice_date->format('d/m/Y') }}</p>
          </div>
          <div class="text-right">
            <p class="font-mono text-sm">{{ $money($cobro->amount_cents) }}</p>
            <p class="font-mono text-[10px] uppercase tracking-widest {{ $cobro->status === 'pagado' ? 'text-emerald-300' : ($cobro->status === 'fallido' ? 'text-red-300' : 'text-amber-300') }}">{{ $cobro->status }}</p>
          </div>
        </li>
      @empty
        <li class="py-6 text-center text-sm text-zinc-500">Todavía no hay cobros registrados.</li>
      @endforelse
    </ul>
  </section>

@endsection
