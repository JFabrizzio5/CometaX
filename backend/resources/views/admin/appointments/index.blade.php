@extends('layouts.admin')

@section('title', 'Citas')

@php
    $badge = fn (string $status): string => match ($status) {
        'confirmada' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'solicitada' => 'border-sky-500/30 bg-sky-500/10 text-sky-300',
        'completada' => 'border-zinc-600/40 bg-zinc-600/10 text-zinc-400',
        'cancelada' => 'border-red-500/30 bg-red-500/10 text-red-300',
        default => 'border-zinc-700/50 bg-zinc-800/30 text-zinc-500',
    };
@endphp

@section('admin-content')

  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Citas</h1>
      <p class="mt-1 text-sm text-zinc-400">Agenda con clientes y prospectos.</p>
    </div>
    <a href="{{ route('admin.appointments.create') }}"
       class="h-11 flex items-center rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">
      + Nueva cita
    </a>
  </div>

  <div class="mt-6 flex gap-2">
    <a href="{{ route('admin.appointments.index') }}"
       class="h-10 rounded-control border px-4 font-mono text-xs uppercase tracking-widest leading-10 transition
              {{ $ver === 'proximas' ? 'border-white/40 bg-white/10 text-white' : 'border-white/10 text-zinc-400 hover:border-white/25 hover:text-white' }}">
      Próximas
    </a>
    <a href="{{ route('admin.appointments.index', ['ver' => 'pasadas']) }}"
       class="h-10 rounded-control border px-4 font-mono text-xs uppercase tracking-widest leading-10 transition
              {{ $ver === 'pasadas' ? 'border-white/40 bg-white/10 text-white' : 'border-white/10 text-zinc-400 hover:border-white/25 hover:text-white' }}">
      Pasadas
    </a>
  </div>

  <div class="mt-6 overflow-x-auto rounded-card border border-white/10 bg-white/[0.03]">
    <table class="w-full min-w-[760px] text-left text-sm">
      <thead>
        <tr class="border-b border-white/10 font-mono text-[11px] uppercase tracking-widest text-zinc-500">
          <th class="px-6 py-4 font-medium">Fecha</th>
          <th class="px-6 py-4 font-medium">Cliente</th>
          <th class="px-6 py-4 font-medium">Tipo</th>
          <th class="px-6 py-4 font-medium">Consultor</th>
          <th class="px-6 py-4 font-medium">Estado</th>
          <th class="px-6 py-4 font-medium">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/5">
        @forelse ($appointments as $appointment)
          <tr class="transition hover:bg-white/[0.02]">
            <td class="px-6 py-4">
              <p class="font-medium">{{ $appointment->appointment_date->format('d/m/Y') }}</p>
              <p class="font-mono text-xs text-zinc-500">{{ substr($appointment->start_time, 0, 5) }}–{{ substr($appointment->end_time, 0, 5) }}</p>
            </td>
            <td class="px-6 py-4 text-zinc-300">{{ $appointment->client?->name ?? $appointment->lead?->name ?? '—' }}</td>
            <td class="px-6 py-4 text-zinc-300">{{ ucfirst(str_replace('_', ' ', $appointment->meeting_type)) }}</td>
            <td class="px-6 py-4 text-zinc-400">{{ $appointment->consultant?->name ?? '—' }}</td>
            <td class="px-6 py-4">
              <span class="inline-flex rounded-full border px-3 py-1 font-mono text-[11px] uppercase tracking-widest {{ $badge($appointment->status) }}">
                {{ $appointment->status }}
              </span>
            </td>
            <td class="px-6 py-4">
              <a href="{{ route('admin.appointments.edit', $appointment) }}"
                 class="font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:text-white">
                Editar
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-6 py-16 text-center text-sm text-zinc-500">
              {{ $ver === 'pasadas' ? 'No hay citas pasadas.' : 'No hay citas próximas. Agenda la primera.' }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

@endsection
