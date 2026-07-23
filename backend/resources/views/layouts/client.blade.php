{{--
  Layout del portal de cliente con navegación lateral. Las vistas del cliente
  hacen @extends('layouts.client') y llenan @section('client-content').
  Hereda de layouts.app (fondo + barra "ver como cliente" si aplica).
--}}
@extends('layouts.app')

@php
    $u = auth('web')->user();
    $nav = [
        ['dashboard', 'dashboard', 'Resumen'],
        ['client.projects', 'client.projects*', 'Proyectos'],
        ['client.incidents', 'client.incidents*', 'Incidencias'],
        ['client.invoices', 'client.invoices', 'Facturación'],
        ['client.contracts', 'client.contracts', 'Contratos'],
        ['client.calendar', 'client.calendar', 'Calendario'],
        ['client.support', 'client.support', 'Soporte'],
    ];
@endphp

@section('content')
<div class="flex min-h-screen">

  <aside class="hidden lg:flex w-60 shrink-0 flex-col border-r border-white/10 bg-black/40 px-5 py-8">
    <div class="flex items-center gap-3 px-1">
      <span class="text-white">@include('partials.logo', ['class' => 'h-9 w-auto'])</span>
      <div>
        <p class="text-sm font-semibold tracking-tight leading-none">{{ config('app.name') }}</p>
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mt-1">Portal cliente</p>
      </div>
    </div>

    <nav class="mt-10 flex-1 space-y-1">
      @foreach ($nav as [$route, $pattern, $label])
        <a href="{{ route($route) }}"
           class="flex items-center rounded-control px-4 py-2.5 text-sm transition
                  {{ request()->routeIs($pattern) ? 'bg-white/10 text-white font-medium' : 'text-zinc-400 hover:bg-white/5 hover:text-white' }}">
          {{ $label }}
        </a>
      @endforeach
    </nav>

    <div class="border-t border-white/10 pt-5">
      <p class="px-1 text-sm font-medium text-zinc-200 truncate">{{ $u?->name }}</p>
      <p class="px-1 font-mono text-[10px] text-zinc-500 truncate">{{ $u?->client?->name }}</p>
      <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf
        <button class="w-full h-10 rounded-control border border-white/15 font-mono text-[11px] uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">Salir</button>
      </form>
    </div>
  </aside>

  <div class="lg:hidden fixed inset-x-0 top-0 z-20 border-b border-white/10 bg-black/70 backdrop-blur">
    <div class="flex items-center justify-between px-4 py-3">
      <span class="flex items-center gap-2 text-white">@include('partials.logo', ['class' => 'h-6 w-auto'])<span class="font-mono text-[11px] uppercase tracking-widest text-zinc-300">Portal</span></span>
      <form method="POST" action="{{ route('logout') }}">@csrf<button class="font-mono text-[11px] uppercase tracking-widest text-zinc-400 hover:text-white">Salir</button></form>
    </div>
    <nav class="flex gap-1 overflow-x-auto px-3 pb-3">
      @foreach ($nav as [$route, $pattern, $label])
        <a href="{{ route($route) }}" class="whitespace-nowrap rounded-control px-3 py-1.5 text-xs font-mono uppercase tracking-widest transition {{ request()->routeIs($pattern) ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-white' }}">{{ $label }}</a>
      @endforeach
    </nav>
  </div>

  <main class="flex-1 min-w-0 px-6 py-10 lg:py-12 pt-32 lg:pt-12">
    <div class="mx-auto max-w-6xl">

      @if (session('status'))
        <div class="mb-6 rounded-control border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</div>
      @endif
      @if ($errors->any())
        <div class="mb-6 rounded-control border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
          <ul class="list-disc pl-4 space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      @endif

      @yield('client-content')
    </div>
  </main>

</div>
@endsection
