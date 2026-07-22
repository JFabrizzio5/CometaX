{{--
  Layout del panel interno (staff). Las vistas admin extienden esto y llenan
  @section('admin-content'). Marca la pestaña activa con routeIs sobre el
  prefijo de cada módulo.
--}}
@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-6 py-10">

  <header class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="h-9 w-9 rounded-control bg-white flex items-center justify-center">
        <span class="font-mono text-[10px] font-bold text-black">CX</span>
      </div>
      <div>
        <p class="text-sm font-semibold tracking-tight leading-none">{{ config('app.name') }}</p>
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mt-1">Panel interno</p>
      </div>
    </div>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="h-10 rounded-control border border-white/15 px-4 font-mono text-[11px] uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
        Salir · {{ auth('consultant')->user()->email }}
      </button>
    </form>
  </header>

  <nav class="mt-8 flex flex-wrap gap-2">
    @foreach ([
      ['admin.dashboard', 'admin.dashboard', 'Resumen'],
      ['admin.clients.index', 'admin.clients.*', 'Clientes'],
      ['admin.projects.index', 'admin.projects.*', 'Proyectos'],
      ['admin.incidents.index', 'admin.incidents.*', 'Incidencias'],
      ['admin.appointments.index', 'admin.appointments.*', 'Citas'],
      ['admin.announcements.index', 'admin.announcements.*', 'Avisos'],
      ['admin.subscriptions', 'admin.subscriptions', 'Suscripciones'],
    ] as [$route, $pattern, $label])
      <a href="{{ route($route) }}"
         class="h-10 rounded-control border px-4 text-xs font-mono uppercase tracking-widest leading-10 transition
                {{ request()->routeIs($pattern) ? 'border-white/40 bg-white/10 text-white' : 'border-white/10 text-zinc-400 hover:border-white/25 hover:text-white' }}">
        {{ $label }}
      </a>
    @endforeach
  </nav>

  @if (session('status'))
    <div class="mt-6 rounded-control border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
      {{ session('status') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="mt-6 rounded-control border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
      <ul class="list-disc pl-4 space-y-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="mt-8">
    @yield('admin-content')
  </div>

</div>
@endsection
