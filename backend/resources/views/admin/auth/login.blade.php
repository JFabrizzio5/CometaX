@extends('layouts.app')

@section('title', 'Acceso staff')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-6 py-16">

  <div class="flex items-center gap-3 mb-10">
    <div class="h-10 w-10 rounded-control bg-white flex items-center justify-center">
      <span class="font-mono text-xs font-bold text-black">CX</span>
    </div>
    <div>
      <p class="text-sm font-semibold tracking-tight">{{ config('app.name') }}</p>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Panel interno</p>
    </div>
  </div>

  <div class="w-full max-w-md rounded-card border border-white/10 bg-white/[0.03] p-8">
    <h1 class="text-2xl font-semibold tracking-tight">Acceso del equipo</h1>
    <p class="mt-2 text-sm text-zinc-400">Ingresa con tu correo y contraseña.</p>

    @if (session('status'))
      <div class="mt-6 rounded-control border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
        {{ session('status') }}
      </div>
    @endif
    @error('email')
      <div class="mt-6 rounded-control border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-8 space-y-5">
      @csrf
      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Correo</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <div>
        <div class="flex items-center justify-between">
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Contraseña</label>
          <a href="{{ route('admin.password.request') }}" class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-white transition">Definir / olvidé</a>
        </div>
        <input type="password" name="password" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <label class="flex items-center gap-2 text-xs text-zinc-500">
        <input type="checkbox" name="remember" class="h-3.5 w-3.5 rounded-[4px] bg-white/5 border border-white/20 accent-white" />
        Mantener sesión iniciada
      </label>
      <button class="h-12 w-full rounded-control bg-white px-5 text-sm font-semibold text-black transition hover:bg-zinc-200">
        Entrar al panel
      </button>
    </form>
  </div>
</div>
@endsection
