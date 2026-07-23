{{--
  Layout del panel interno (staff) con navegación lateral con iconos y secciones,
  al estilo de la demo. Las vistas admin hacen @extends('layouts.admin') y llenan
  @section('admin-content'). «Consultores» solo se muestra a super_admin.
--}}
@extends('layouts.app')

@php
    $consultant = auth('consultant')->user();

    // [route, patrón, etiqueta, icono svg path]
    $ic = [
        'home' => 'M2.25 12l8.955-8.955a1.125 1.125 0 011.59 0L21.75 12M4.5 9.75v9.375c0 .621.504 1.125 1.125 1.125H9.75v-5.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V20.25h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
        'users' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'folder' => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
        'warn' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        'cal' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'clock' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'bell' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
        'card' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 6h16.5a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-9a1.5 1.5 0 011.5-1.5z',
        'idcard' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
    ];

    $groups = [
        'Operación' => [
            ['admin.dashboard', 'admin.dashboard', 'Resumen', $ic['home']],
            ['admin.clients.index', 'admin.clients.*', 'Clientes', $ic['users']],
            ['admin.projects.index', 'admin.projects.*', 'Proyectos', $ic['folder']],
            ['admin.incidents.index', 'admin.incidents.*', 'Incidencias', $ic['warn']],
            ['admin.appointments.calendar', 'admin.appointments.calendar', 'Calendario', $ic['cal']],
            ['admin.appointments.index', 'admin.appointments.index', 'Citas', $ic['clock']],
        ],
        'Negocio' => [
            ['admin.announcements.index', 'admin.announcements.*', 'Avisos', $ic['bell']],
            ['admin.subscriptions', 'admin.subscriptions', 'Suscripciones', $ic['card']],
        ],
    ];
    if ($consultant?->isSuperAdmin()) {
        $groups['Negocio'][] = ['admin.consultants.index', 'admin.consultants.*', 'Consultores', $ic['idcard']];
    }
    $flat = collect($groups)->flatten(1);
@endphp

@section('content')
<div class="flex min-h-screen">

  {{-- Sidebar --}}
  <aside class="hidden lg:flex w-64 shrink-0 flex-col border-r border-white/10 bg-black/40 px-4 py-6">
    <div class="flex items-center gap-3 px-2">
      <span class="text-white">@include('partials.logo', ['class' => 'h-9 w-auto'])</span>
      <div>
        <p class="text-sm font-semibold tracking-tight leading-none">{{ config('app.name') }}</p>
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mt-1">Panel interno</p>
      </div>
    </div>

    <nav class="mt-8 flex-1 overflow-y-auto space-y-6">
      @foreach ($groups as $section => $items)
        <div class="space-y-1">
          <p class="px-3 pb-1 font-mono text-[10px] uppercase tracking-widest text-zinc-600">{{ $section }}</p>
          @foreach ($items as [$route, $pattern, $label, $icon])
            @php $active = request()->routeIs($pattern); @endphp
            <a href="{{ route($route) }}"
               class="flex items-center gap-3 rounded-control px-4 py-2.5 text-sm font-medium transition
                      {{ $active ? 'bg-white text-black' : 'text-zinc-400 hover:bg-white/5 hover:text-white' }}">
              <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
              {{ $label }}
            </a>
          @endforeach
        </div>
      @endforeach
    </nav>

    <div class="border-t border-white/10 pt-4">
      <p class="px-2 text-sm font-medium text-zinc-200 truncate">{{ $consultant?->name }}</p>
      <p class="px-2 font-mono text-[10px] text-zinc-500 truncate">{{ $consultant?->email }}</p>
      <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button class="w-full h-10 rounded-control border border-white/15 font-mono text-[11px] uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">Salir</button>
      </form>
    </div>
  </aside>

  {{-- Nav superior (móvil) --}}
  <div class="lg:hidden fixed inset-x-0 top-0 z-20 border-b border-white/10 bg-black/70 backdrop-blur">
    <div class="flex items-center justify-between px-4 py-3">
      <span class="flex items-center gap-2 text-white">
        @include('partials.logo', ['class' => 'h-6 w-auto'])
        <span class="font-mono text-[11px] uppercase tracking-widest text-zinc-300">Interno</span>
      </span>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="font-mono text-[11px] uppercase tracking-widest text-zinc-400 hover:text-white">Salir</button>
      </form>
    </div>
    <nav class="flex gap-1 overflow-x-auto px-3 pb-3">
      @foreach ($flat as [$route, $pattern, $label, $icon])
        <a href="{{ route($route) }}"
           class="flex items-center gap-1.5 whitespace-nowrap rounded-control px-3 py-1.5 text-xs font-mono uppercase tracking-widest transition
                  {{ request()->routeIs($pattern) ? 'bg-white text-black' : 'text-zinc-500 hover:text-white' }}">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
          {{ $label }}
        </a>
      @endforeach
    </nav>
  </div>

  {{-- Contenido --}}
  <main class="flex-1 min-w-0 px-6 py-10 lg:py-12 pt-32 lg:pt-12">
    <div class="mx-auto max-w-6xl">

      @if (session('status'))
        <div class="mb-6 rounded-control border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</div>
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
