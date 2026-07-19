@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mx-auto max-w-7xl px-6 py-12">

  <header class="flex flex-wrap items-start justify-between gap-6">
    <div>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">
        {{ auth()->user()->client->name }}
      </p>
      <h1 class="mt-2 text-3xl font-semibold tracking-tight">Hola, {{ auth()->user()->name }}</h1>
    </div>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="h-11 rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
        Salir
      </button>
    </form>
  </header>

  <div class="mt-10 rounded-card border border-white/10 bg-white/[0.03] p-8">
    <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Plan</p>
    <p class="mt-3 text-2xl font-semibold tracking-tight">
      {{ auth()->user()->client->plan?->name ?? 'Sin plan asignado' }}
    </p>
    <p class="mt-2 text-sm text-zinc-400">
      Aquí van las pantallas de proyectos, incidencias y facturación.
    </p>
  </div>

</div>
@endsection
