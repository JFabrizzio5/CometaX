@extends('layouts.app')

@section('title', 'Crear cuenta')

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
    <h1 class="text-2xl font-semibold tracking-tight">Crea tu cuenta</h1>
    <p class="mt-2 text-sm text-zinc-400">Te enviamos un correo para verificar tu dirección.</p>

    <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-4">
      @csrf
      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Nombre / empresa</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Correo</label>
        <input type="email" name="email" value="{{ old('email') }}" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Contraseña</label>
        <input type="password" name="password" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Repetir contraseña</label>
        <input type="password" name="password_confirmation" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>
      <button class="h-12 w-full rounded-control bg-white px-5 text-sm font-semibold text-black transition hover:bg-zinc-200">
        Crear cuenta
      </button>
    </form>

    <p class="mt-4 text-center text-sm text-zinc-400">
      ¿Ya tienes cuenta?
      <a href="{{ route('login') }}" class="text-white underline underline-offset-4 hover:text-zinc-200">Inicia sesión</a>
    </p>
  </div>

</div>
@endsection
