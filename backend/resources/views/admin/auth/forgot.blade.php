@extends('layouts.app')

@section('title', 'Definir contraseña')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-6 py-16">
  <div class="w-full max-w-md rounded-card border border-white/10 bg-white/[0.03] p-8">
    <h1 class="text-2xl font-semibold tracking-tight">Definir / restablecer contraseña</h1>
    <p class="mt-2 text-sm text-zinc-400">Te enviamos un enlace a tu correo de staff.</p>

    @if (session('status'))
      <div class="mt-6 rounded-control border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
        {{ session('status') }}
      </div>
    @endif
    @error('email')
      <div class="mt-6 rounded-control border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('admin.password.email') }}" class="mt-8 space-y-5">
      @csrf
      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Correo</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <button class="h-12 w-full rounded-control bg-white px-5 text-sm font-semibold text-black transition hover:bg-zinc-200">
        Enviar enlace
      </button>
      <a href="{{ route('admin.login') }}" class="block text-center font-mono text-[11px] uppercase tracking-widest text-zinc-500 hover:text-white transition">Volver al login</a>
    </form>
  </div>
</div>
@endsection
