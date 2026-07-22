@extends('layouts.admin')

@section('title', 'Clientes')

@section('admin-content')

  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Clientes</h1>
      <p class="mt-1 text-sm text-zinc-400">Tu cartera completa.</p>
    </div>
    <a href="{{ route('admin.clients.create') }}"
       class="h-11 flex items-center rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">
      + Nuevo cliente
    </a>
  </div>

  <form method="GET" action="{{ route('admin.clients.index') }}" class="mt-6 flex gap-2 max-w-md">
    <input type="search" name="q" value="{{ $q }}" placeholder="Buscar por nombre o correo…"
      class="h-11 w-full rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition" />
    <button class="h-11 rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
      Buscar
    </button>
  </form>

  <div class="mt-6 overflow-x-auto rounded-card border border-white/10 bg-white/[0.03]">
    <table class="w-full min-w-[700px] text-left text-sm">
      <thead>
        <tr class="border-b border-white/10 font-mono text-[11px] uppercase tracking-widest text-zinc-500">
          <th class="px-6 py-4 font-medium">Cliente</th>
          <th class="px-6 py-4 font-medium">Plan</th>
          <th class="px-6 py-4 font-medium">Usuarios</th>
          <th class="px-6 py-4 font-medium">Proyectos</th>
          <th class="px-6 py-4 font-medium">Alta</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/5">
        @forelse ($clients as $client)
          <tr class="transition hover:bg-white/[0.02]">
            <td class="px-6 py-4">
              <a href="{{ route('admin.clients.show', $client) }}" class="font-medium hover:underline underline-offset-4">{{ $client->name }}</a>
              <p class="font-mono text-xs text-zinc-500">{{ $client->contact_email ?? '—' }}</p>
            </td>
            <td class="px-6 py-4 text-zinc-300">{{ $client->plan?->name ?? 'Sin plan' }}</td>
            <td class="px-6 py-4 font-mono text-zinc-400">{{ $client->users_count }}</td>
            <td class="px-6 py-4 font-mono text-zinc-400">{{ $client->projects_count }}</td>
            <td class="px-6 py-4 font-mono text-xs text-zinc-400">{{ $client->created_at->format('d/m/Y') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="px-6 py-16 text-center text-sm text-zinc-500">
              {{ $q !== '' ? 'Sin resultados para «'.$q.'».' : 'Todavía no hay clientes. Crea el primero.' }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

@endsection
