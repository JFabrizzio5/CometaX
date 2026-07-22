@extends('layouts.admin')

@section('title', 'Consultores')

@php
    $roleBadge = fn (string $r) => match ($r) {
        'super_admin' => ['Super admin', 'border-fuchsia-500/30 bg-fuchsia-500/10 text-fuchsia-300'],
        'admin' => ['Admin', 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'],
        default => ['Consultor', 'border-white/15 bg-white/5 text-zinc-300'],
    };
@endphp

@section('admin-content')

  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Consultores</h1>
      <p class="mt-1 text-sm text-zinc-400">Tu equipo interno y sus accesos.</p>
    </div>
    <a href="{{ route('admin.consultants.create') }}"
       class="h-11 flex items-center rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">
      + Nuevo consultor
    </a>
  </div>

  <div class="mt-6 overflow-x-auto rounded-card border border-white/10 bg-white/[0.03]">
    <table class="w-full min-w-[640px] text-left text-sm">
      <thead>
        <tr class="border-b border-white/10 font-mono text-[11px] uppercase tracking-widest text-zinc-500">
          <th class="px-6 py-4 font-medium">Consultor</th>
          <th class="px-6 py-4 font-medium">Rol</th>
          <th class="px-6 py-4 font-medium">Título</th>
          <th class="px-6 py-4 font-medium">Proyectos</th>
          <th class="px-6 py-4 font-medium">Acceso</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/5">
        @forelse ($consultants as $consultant)
          @php [$label, $tone] = $roleBadge($consultant->role); @endphp
          <tr class="transition hover:bg-white/[0.02]">
            <td class="px-6 py-4">
              <a href="{{ route('admin.consultants.show', $consultant) }}" class="font-medium hover:underline underline-offset-4">{{ $consultant->name }}</a>
              <p class="font-mono text-xs text-zinc-500">{{ $consultant->email ?? '—' }}</p>
            </td>
            <td class="px-6 py-4">
              <span class="inline-flex rounded-full border px-3 py-1 font-mono text-[11px] uppercase tracking-widest {{ $tone }}">{{ $label }}</span>
            </td>
            <td class="px-6 py-4 text-zinc-300">{{ $consultant->title ?? '—' }}</td>
            <td class="px-6 py-4 font-mono text-zinc-400">{{ $consultant->projects_count }}</td>
            <td class="px-6 py-4 font-mono text-[11px] uppercase tracking-widest {{ $consultant->password ? 'text-emerald-300' : 'text-amber-300' }}">
              {{ $consultant->password ? 'Activo' : 'Pendiente' }}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-6 py-16 text-center text-sm text-zinc-500">Todavía no hay consultores. Crea el primero.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

@endsection
