@extends('layouts.app')

@section('title', 'Acceso')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-6 py-16">

  <div class="flex items-center gap-3 mb-10">
    <span class="text-white">@include('partials.logo', ['class' => 'h-10 w-auto'])</span>
    <div>
      <p class="text-sm font-semibold tracking-tight">{{ config('app.name') }}</p>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Portal de clientes</p>
    </div>
  </div>

  <div class="w-full max-w-md rounded-card border border-white/10 bg-white/[0.03] p-8">
    <h1 class="text-2xl font-semibold tracking-tight">Entra a tu panel</h1>
    <p class="mt-2 text-sm text-zinc-400">
      Usa tu cuenta de Google. Si es tu primera vez, se crea tu espacio automáticamente.
    </p>

    @error('google')
      <div class="mt-6 rounded-control border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
        {{ $message }}
      </div>
    @enderror

    <a href="{{ route('auth.google.redirect') }}"
       class="mt-8 flex h-12 w-full items-center justify-center gap-3 rounded-control bg-white px-5 text-sm font-semibold text-black transition hover:bg-zinc-200">
      <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.63h6.46a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.58-5.17 3.58-8.8z"/>
        <path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.94-2.91l-3.88-3.01c-1.08.72-2.45 1.15-4.06 1.15-3.12 0-5.77-2.11-6.71-4.95H1.28v3.1A12 12 0 0 0 12 24z"/>
        <path fill="#FBBC05" d="M5.29 14.28a7.2 7.2 0 0 1 0-4.56v-3.1H1.28a12 12 0 0 0 0 10.76l4.01-3.1z"/>
        <path fill="#EA4335" d="M12 4.75c1.76 0 3.34.61 4.59 1.8l3.44-3.44C17.95 1.18 15.23 0 12 0A12 12 0 0 0 1.28 6.62l4.01 3.1C6.23 6.86 8.88 4.75 12 4.75z"/>
      </svg>
      Continuar con Google
    </a>

    @if (config('features.demo_login'))
      <div class="mt-6 flex items-center gap-3">
        <div class="h-px flex-1 bg-white/10"></div>
        <span class="font-mono text-[10px] uppercase tracking-widest text-zinc-600">demo</span>
        <div class="h-px flex-1 bg-white/10"></div>
      </div>
      <a href="{{ route('demo.entrar') }}"
         class="mt-6 flex h-12 w-full items-center justify-center gap-3 rounded-control border border-white/15 px-5 text-sm font-semibold text-zinc-200 transition hover:border-white/30 hover:text-white">
        Ver panel demo
      </a>
    @endif

    <p class="mt-6 text-center font-mono text-[11px] uppercase tracking-widest text-zinc-600">
      Acceso cifrado · OAuth 2.0
    </p>
  </div>

</div>
@endsection
