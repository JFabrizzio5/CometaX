@extends('layouts.admin')

@section('title', $consultant->exists ? 'Editar consultor' : 'Nuevo consultor')

@php
    $roleLabels = ['consultant' => 'Consultor', 'admin' => 'Admin', 'super_admin' => 'Super admin'];
@endphp

@section('admin-content')

  <div class="max-w-2xl">
    <h1 class="text-2xl font-semibold tracking-tight">{{ $consultant->exists ? "Editar «{$consultant->name}»" : 'Nuevo consultor' }}</h1>
    @unless ($consultant->exists)
      <p class="mt-1 text-sm text-zinc-400">Al crearlo se genera un enlace para que defina su contraseña.</p>
    @endunless

    <form method="POST"
          action="{{ $consultant->exists ? route('admin.consultants.update', $consultant) : route('admin.consultants.store') }}"
          class="mt-8 rounded-card border border-white/10 bg-white/[0.03] p-8 space-y-5">
      @csrf
      @if ($consultant->exists) @method('PUT') @endif

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Nombre *</label>
        <input type="text" name="name" value="{{ old('name', $consultant->name) }}" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Correo *</label>
          <input type="email" name="email" value="{{ old('email', $consultant->email) }}" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Título / puesto</label>
          <input type="text" name="title" value="{{ old('title', $consultant->title) }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Rol *</label>
        <select name="role"
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
          @foreach ($roles as $role)
            <option value="{{ $role }}" @selected(old('role', $consultant->role ?? 'consultant') === $role)>{{ $roleLabels[$role] ?? $role }}</option>
          @endforeach
        </select>
        <p class="mt-2 font-mono text-[10px] text-zinc-600">Consultor: solo sus proyectos. Admin: gestión. Super admin: además gestiona consultores.</p>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button class="h-12 rounded-control bg-white px-6 text-sm font-semibold text-black transition hover:bg-zinc-200">
          {{ $consultant->exists ? 'Guardar cambios' : 'Crear consultor' }}
        </button>
        <a href="{{ $consultant->exists ? route('admin.consultants.show', $consultant) : route('admin.consultants.index') }}"
           class="h-12 flex items-center rounded-control border border-white/15 px-5 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
          Cancelar
        </a>
      </div>
    </form>
  </div>

@endsection
