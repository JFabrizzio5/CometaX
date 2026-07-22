@extends('layouts.app')

@section('title', 'Nueva contraseña')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-6 py-16">
  <div class="w-full max-w-md rounded-card border border-white/10 bg-white/[0.03] p-8">
    <h1 class="text-2xl font-semibold tracking-tight">Define tu contraseña</h1>
    <p class="mt-2 text-sm text-zinc-400">Mínimo 8 caracteres.</p>

    @error('email')
      <div class="mt-6 rounded-control border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('admin.password.update') }}" class="mt-8 space-y-5">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}" />
      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Correo</label>
        <input type="email" name="email" value="{{ old('email', $email) }}" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Contraseña</label>
        <input type="password" name="password" required autofocus
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Repetir contraseña</label>
        <input type="password" name="password_confirmation" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <button class="h-12 w-full rounded-control bg-white px-5 text-sm font-semibold text-black transition hover:bg-zinc-200">
        Guardar contraseña
      </button>
    </form>
  </div>
</div>
@endsection
