@extends('layouts.app')

@section('title', 'Verifica tu correo')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-6 py-16 text-center">

  <span class="text-white mb-8">@include('partials.logo', ['class' => 'h-10 w-auto'])</span>

  <div class="w-full max-w-md rounded-card border border-white/10 bg-white/[0.03] p-8">
    <div class="mx-auto h-12 w-12 rounded-full bg-amber-500/15 border border-amber-500/30 flex items-center justify-center">
      <svg class="h-6 w-6 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
      </svg>
    </div>

    <h1 class="mt-6 text-2xl font-semibold tracking-tight">Verifica tu correo</h1>
    <p class="mt-2 text-sm text-zinc-400">
      Te enviamos un enlace a <span class="text-zinc-200">{{ auth('web')->user()?->email }}</span>.
      Ábrelo para activar tu cuenta.
    </p>

    @if (session('status'))
      <div class="mt-6 rounded-control border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
        {{ session('status') }}
      </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
      @csrf
      <button class="h-12 w-full rounded-control bg-white px-5 text-sm font-semibold text-black transition hover:bg-zinc-200">
        Reenviar correo
      </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
      @csrf
      <button class="font-mono text-[11px] uppercase tracking-widest text-zinc-500 transition hover:text-white">
        Cerrar sesión
      </button>
    </form>
  </div>

</div>
@endsection
