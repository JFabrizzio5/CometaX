@extends('layouts.app')

@section('title', 'Planes')

@section('content')
<div class="mx-auto max-w-5xl px-6 py-12">

  <header class="flex flex-wrap items-start justify-between gap-6">
    <div>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Facturación</p>
      <h1 class="mt-2 text-3xl font-semibold tracking-tight">Elige tu plan</h1>
      <p class="mt-2 text-sm text-zinc-400">
        Plan actual: <span class="text-white">{{ $client->plan?->name ?? 'Sin plan asignado' }}</span>
      </p>
    </div>
    <a href="{{ route('dashboard') }}"
       class="h-11 flex items-center rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
      Volver
    </a>
  </header>

  @if (session('status'))
    <div class="mt-8 rounded-control border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
      {{ session('status') }}
    </div>
  @endif

  <div class="mt-10 grid gap-6 sm:grid-cols-2">
    @foreach ($plans as $plan)
      <div class="rounded-card border border-white/10 bg-white/[0.03] p-8 flex flex-col">
        <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">{{ $plan->name }}</p>

        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-semibold tracking-tight">{{ $plan->priceDomiciliadoLabel() ?? $plan->priceOnetimeLabel() }}</span>
          <span class="text-sm text-zinc-400">/mes domiciliado</span>
        </div>
        <p class="mt-1 text-sm text-zinc-500">o {{ $plan->priceOnetimeLabel() }} en pago único mensual</p>

        @if ($plan->description)
          <p class="mt-4 text-sm text-zinc-400">{{ $plan->description }}</p>
        @endif

        <div class="mt-auto pt-8 space-y-3">
          @if ($plan->isBillable())
            <form method="POST" action="{{ route('billing.domiciliar', $plan) }}">
              @csrf
              <button class="h-12 w-full rounded-control bg-white px-5 text-sm font-semibold text-black transition hover:bg-zinc-200">
                Domiciliar tarjeta · {{ $plan->priceDomiciliadoLabel() }}/mes
              </button>
            </form>
            <form method="POST" action="{{ route('billing.unico', $plan) }}">
              @csrf
              <button class="h-12 w-full rounded-control border border-white/15 px-5 text-sm font-semibold text-zinc-200 transition hover:border-white/30 hover:text-white">
                Pagar una vez · {{ $plan->priceOnetimeLabel() }}
              </button>
            </form>
          @else
            <p class="rounded-control border border-white/10 px-4 py-3 text-center text-xs text-zinc-500">
              Plan no disponible para pago en línea todavía.
            </p>
          @endif
        </div>
      </div>
    @endforeach
  </div>

  <p class="mt-8 text-center font-mono text-[11px] uppercase tracking-widest text-zinc-600">
    Pago procesado por Stripe · No guardamos tu tarjeta
  </p>

</div>
@endsection
