@extends('layouts.app')

@section('title', 'Pago recibido')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-6 py-16 text-center">
  <div class="h-14 w-14 rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
    <svg class="h-7 w-7 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
      <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </div>
  <h1 class="mt-6 text-2xl font-semibold tracking-tight">Pago recibido</h1>
  <p class="mt-2 max-w-md text-sm text-zinc-400">
    Estamos confirmando tu pago con Stripe. Tu plan se activa en cuanto se procese
    (unos segundos). Puedes volver a tu panel.
  </p>
  <a href="{{ route('dashboard') }}"
     class="mt-8 h-12 flex items-center rounded-control bg-white px-6 text-sm font-semibold text-black transition hover:bg-zinc-200">
    Ir a mi panel
  </a>
</div>
@endsection
