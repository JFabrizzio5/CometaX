@extends('layouts.app')

@section('title', 'Suscripciones')

@php
    $money = fn (int $cents) => '$' . number_format($cents / 100, 2);
    $badge = function (?string $status): array {
        return match ($status) {
            'active' => ['Activa', 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'],
            'trialing' => ['Prueba', 'border-sky-500/30 bg-sky-500/10 text-sky-300'],
            'past_due' => ['Vencida', 'border-amber-500/30 bg-amber-500/10 text-amber-300'],
            'unpaid' => ['Sin pago', 'border-red-500/30 bg-red-500/10 text-red-300'],
            'incomplete' => ['Incompleta', 'border-amber-500/30 bg-amber-500/10 text-amber-300'],
            'canceled' => ['Cancelada', 'border-zinc-600/40 bg-zinc-600/10 text-zinc-400'],
            null => ['Sin suscripción', 'border-zinc-700/50 bg-zinc-800/30 text-zinc-500'],
            default => [$status, 'border-zinc-600/40 bg-zinc-600/10 text-zinc-400'],
        };
    };
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-6 py-12">

  <header class="flex flex-wrap items-start justify-between gap-6">
    <div>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Panel interno</p>
      <h1 class="mt-2 text-3xl font-semibold tracking-tight">Suscripciones</h1>
      <p class="mt-1 text-sm text-zinc-400">Quién está pagando, quién está por caerse.</p>
    </div>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="h-11 rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
        Salir · {{ auth('consultant')->user()->email }}
      </button>
    </form>
  </header>

  <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([
      ['MRR', $money($stats['mrr_cents']), 'text-white'],
      ['Pagando', $stats['pagando'], 'text-emerald-300'],
      ['En riesgo', $stats['en_riesgo'], 'text-amber-300'],
      ['Sin suscripción', $stats['sin_suscripcion'], 'text-zinc-400'],
    ] as [$label, $value, $tone])
      <div class="rounded-card border border-white/10 bg-white/[0.03] p-6">
        <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">{{ $label }}</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight {{ $tone }}">{{ $value }}</p>
      </div>
    @endforeach
  </div>

  <nav class="mt-10 flex flex-wrap gap-2">
    @foreach ([
      '' => 'Todos',
      'pagando' => 'Pagando',
      'riesgo' => 'En riesgo',
      'sin-suscripcion' => 'Sin suscripción',
    ] as $value => $label)
      <a href="{{ route('admin.subscriptions', $value === '' ? [] : ['estado' => $value]) }}"
         class="h-10 rounded-control border px-4 text-xs font-mono uppercase tracking-widest leading-10 transition
                {{ $filter === $value ? 'border-white/40 bg-white/10 text-white' : 'border-white/10 text-zinc-400 hover:border-white/25 hover:text-white' }}">
        {{ $label }}
      </a>
    @endforeach
  </nav>

  <div class="mt-6 overflow-x-auto rounded-card border border-white/10 bg-white/[0.03]">
    <table class="w-full min-w-[860px] text-left text-sm">
      <thead>
        <tr class="border-b border-white/10 font-mono text-[11px] uppercase tracking-widest text-zinc-500">
          <th class="px-6 py-4 font-medium">Cliente</th>
          <th class="px-6 py-4 font-medium">Plan</th>
          <th class="px-6 py-4 font-medium">Estado</th>
          <th class="px-6 py-4 font-medium">Importe</th>
          <th class="px-6 py-4 font-medium">Vence / termina</th>
          <th class="px-6 py-4 font-medium">Usuarios</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/5">
        @forelse ($clients as $client)
          @php [$label, $tone] = $badge($client['stripe_status']); @endphp
          <tr class="transition hover:bg-white/[0.02]">
            <td class="px-6 py-4">
              <p class="font-medium">{{ $client['name'] }}</p>
              <p class="font-mono text-xs text-zinc-500">{{ $client['contact_email'] ?? '—' }}</p>
            </td>
            <td class="px-6 py-4 text-zinc-300">{{ $client['plan_name'] ?? '—' }}</td>
            <td class="px-6 py-4">
              <span class="inline-flex rounded-full border px-3 py-1 font-mono text-[11px] uppercase tracking-widest {{ $tone }}">
                {{ $label }}
              </span>
            </td>
            <td class="px-6 py-4 font-mono text-zinc-300">
              {{ $client['price_cents'] > 0 ? $money($client['price_cents']) : '—' }}
            </td>
            <td class="px-6 py-4 font-mono text-xs text-zinc-400">
              @if ($client['ends_at'])
                Termina {{ $client['ends_at']->format('d/m/Y') }}
              @elseif ($client['trial_ends_at'])
                Prueba hasta {{ $client['trial_ends_at']->format('d/m/Y') }}
              @else
                —
              @endif
            </td>
            <td class="px-6 py-4 font-mono text-zinc-400">{{ $client['users_count'] }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-6 py-16 text-center text-sm text-zinc-500">
              Todavía no hay clientes registrados.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>
@endsection
