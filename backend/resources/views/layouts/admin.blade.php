{{--
  Layout del panel interno (staff) con navegación lateral izquierda. Las vistas
  admin extienden esto y llenan @section('admin-content'). Marca la pestaña
  activa con routeIs sobre el prefijo de cada módulo. «Consultores» solo se
  muestra a super_admin.
--}}
@extends('layouts.app')

@php
    $consultant = auth('consultant')->user();
    $nav = [
        ['admin.dashboard', 'admin.dashboard', 'Resumen'],
        ['admin.clients.index', 'admin.clients.*', 'Clientes'],
        ['admin.projects.index', 'admin.projects.*', 'Proyectos'],
        ['admin.incidents.index', 'admin.incidents.*', 'Incidencias'],
        ['admin.appointments.index', 'admin.appointments.*', 'Citas'],
        ['admin.announcements.index', 'admin.announcements.*', 'Avisos'],
        ['admin.subscriptions', 'admin.subscriptions', 'Suscripciones'],
    ];
    if ($consultant?->isSuperAdmin()) {
        $nav[] = ['admin.consultants.index', 'admin.consultants.*', 'Consultores'];
    }
@endphp

@section('content')
<div class="flex min-h-screen">

  {{-- Sidebar --}}
  <aside class="hidden lg:flex w-64 shrink-0 flex-col border-r border-white/10 bg-black/40 px-5 py-8">
    <div class="flex items-center gap-3 px-1">
      <div class="h-9 w-9 rounded-control bg-white flex items-center justify-center">
        <span class="font-mono text-[10px] font-bold text-black">CX</span>
      </div>
      <div>
        <p class="text-sm font-semibold tracking-tight leading-none">{{ config('app.name') }}</p>
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mt-1">Panel interno</p>
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
      <p class="px-1 text-sm font-medium text-zinc-200 truncate">{{ $consultant?->name }}</p>
      <p class="px-1 font-mono text-[10px] text-zinc-500 truncate">{{ $consultant?->email }}</p>
      <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button class="w-full h-10 rounded-control border border-white/15 font-mono text-[11px] uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
          Salir
        </button>
      </form>
    </div>
  </aside>

  {{-- Nav superior (móvil) --}}
  <div class="lg:hidden fixed inset-x-0 top-0 z-20 border-b border-white/10 bg-black/70 backdrop-blur">
    <div class="flex items-center justify-between px-4 py-3">
      <span class="font-mono text-[11px] uppercase tracking-widest text-zinc-300">{{ config('app.name') }} · Interno</span>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="font-mono text-[11px] uppercase tracking-widest text-zinc-400 hover:text-white">Salir</button>
      </form>
    </div>
    <nav class="flex gap-1 overflow-x-auto px-3 pb-3">
      @foreach ($nav as [$route, $pattern, $label])
        <a href="{{ route($route) }}"
           class="whitespace-nowrap rounded-control px-3 py-1.5 text-xs font-mono uppercase tracking-widest transition
                  {{ request()->routeIs($pattern) ? 'bg-white/10 text-white' : 'text-zinc-500 hover:text-white' }}">
          {{ $label }}
        </a>
      @endforeach
    </nav>
  </div>

  {{-- Contenido --}}
  <main class="flex-1 min-w-0 px-6 py-10 lg:py-12 pt-32 lg:pt-12">
    <div class="mx-auto max-w-6xl">

      @if (session('status'))
        <div class="mb-6 rounded-control border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
          {{ session('status') }}
        </div>
      @endif

      @if (session('invite_link'))
        <div class="mb-6 rounded-card border border-sky-500/30 bg-sky-500/10 px-4 py-4 text-sm text-sky-100">
          <p class="font-medium">Enlace para definir contraseña (válido 60 min, un solo uso)</p>
          <p class="mt-1 text-sky-200/80">Si no le llega el correo, pásaselo tú directo:</p>
          <input type="text" readonly value="{{ session('invite_link') }}" onclick="this.select()"
            class="mt-3 w-full rounded-control bg-black/30 border border-white/15 px-3 py-2 font-mono text-xs text-sky-100 outline-none" />
        </div>
      @endif

      @if ($errors->any())
        <div class="mb-6 rounded-control border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
          <ul class="list-disc pl-4 space-y-1">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @yield('admin-content')
    </div>
  </main>

</div>
@endsection
