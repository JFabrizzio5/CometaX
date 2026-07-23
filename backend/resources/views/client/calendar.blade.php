@extends('layouts.client')

@section('title', 'Calendario')

@php
    $tipos = [
        'junta_mensual' => 'Junta mensual',
        'soporte_tecnico' => 'Soporte técnico',
        'consultoria_nuevo_proyecto' => 'Nuevo proyecto',
        'revision_contrato' => 'Revisión de contrato',
    ];
    $badge = [
        'confirmada' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'solicitada' => 'border-sky-500/30 bg-sky-500/10 text-sky-300',
        'completada' => 'border-white/15 bg-white/5 text-zinc-400',
        'cancelada' => 'border-red-500/30 bg-red-500/10 text-red-300',
    ];
@endphp

@section('client-content')

  <h1 class="text-3xl font-semibold tracking-tight">Calendario</h1>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Próximas citas</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($proximas as $c)
          <li class="py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm font-medium">{{ str_replace('_', ' ', $c->meeting_type) }}</p>
                <p class="mt-1 font-mono text-xs text-zinc-500">{{ $c->appointment_date?->format('d/m/Y') }} · {{ substr($c->start_time, 0, 5) }}–{{ substr($c->end_time, 0, 5) }}</p>
                @if ($c->location)
                  <p class="mt-1 font-mono text-xs text-zinc-500 truncate">{{ $c->location }}</p>
                @endif
              </div>
              <span class="shrink-0 rounded-full border px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-widest {{ $badge[$c->status] ?? 'border-white/15 bg-white/5 text-zinc-400' }}">{{ $c->status }}</span>
            </div>
          </li>
        @empty
          <li class="py-8 text-center text-sm text-zinc-500">No tienes citas próximas.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Solicitar reunión</h2>
      <form method="POST" action="{{ route('client.appointments.request') }}" class="mt-4 space-y-5">
        @csrf

        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Tipo de reunión</label>
          <select name="meeting_type" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            @foreach ($tipos as $value => $label)
              <option value="{{ $value }}" @selected(old('meeting_type') === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Fecha</label>
          <input type="date" name="appointment_date" required min="{{ now()->toDateString() }}"
            value="{{ old('appointment_date') }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Hora inicio</label>
            <input type="time" name="start_time" required value="{{ old('start_time') }}"
              class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
          </div>
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Hora fin</label>
            <input type="time" name="end_time" required value="{{ old('end_time') }}"
              class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
          </div>
        </div>

        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Notas (opcional)</label>
          <textarea name="notes" rows="3"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition">{{ old('notes') }}</textarea>
        </div>

        <button class="h-12 rounded-control bg-white px-6 text-sm font-semibold text-black transition hover:bg-zinc-200">
          Solicitar reunión
        </button>
      </form>
    </section>

  </div>

  <section class="mt-6 rounded-card border border-white/10 bg-white/[0.03] p-6">
    <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Citas pasadas</h2>
    <ul class="mt-4 divide-y divide-white/5">
      @forelse ($pasadas as $c)
        <li class="py-3 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm">{{ str_replace('_', ' ', $c->meeting_type) }}</p>
            <p class="mt-1 font-mono text-xs text-zinc-500">{{ $c->appointment_date?->format('d/m/Y') }}</p>
          </div>
          <span class="shrink-0 rounded-full border px-2.5 py-0.5 font-mono text-[10px] uppercase tracking-widest {{ $badge[$c->status] ?? 'border-white/15 bg-white/5 text-zinc-400' }}">{{ $c->status }}</span>
        </li>
      @empty
        <li class="py-8 text-center text-sm text-zinc-500">No tienes citas pasadas.</li>
      @endforelse
    </ul>
  </section>

@endsection
