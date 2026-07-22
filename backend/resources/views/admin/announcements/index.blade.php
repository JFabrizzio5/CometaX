@extends('layouts.admin')

@section('title', 'Avisos')

@section('admin-content')

  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Avisos</h1>
      <p class="mt-1 text-sm text-zinc-400">Comunicados para tus clientes.</p>
    </div>
    <a href="{{ route('admin.announcements.create') }}"
       class="h-11 flex items-center rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">
      + Nuevo aviso
    </a>
  </div>

  <div class="mt-6 space-y-4">
    @forelse ($announcements as $announcement)
      <article class="rounded-card border border-white/10 bg-white/[0.03] p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div class="min-w-0">
            <h2 class="font-medium tracking-tight">{{ $announcement->title }}</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-zinc-300">{{ $announcement->body }}</p>
            <p class="mt-3 font-mono text-xs text-zinc-500">
              {{ $announcement->client?->name ?? 'Todos los clientes' }} · {{ $announcement->author?->name ?? '—' }} · {{ $announcement->published_at?->format('d/m/Y H:i') }}
            </p>
          </div>
          <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}">
            @csrf
            @method('DELETE')
            <button onclick="return confirm('¿Eliminar aviso?')"
              class="h-9 rounded-control border border-white/15 px-3 font-mono text-[10px] uppercase tracking-widest text-zinc-400 transition hover:border-red-500/40 hover:text-red-300">
              Eliminar
            </button>
          </form>
        </div>
      </article>
    @empty
      <div class="rounded-card border border-white/10 bg-white/[0.03] px-6 py-16 text-center text-sm text-zinc-500">
        Todavía no hay avisos. Publica el primero.
      </div>
    @endforelse
  </div>

@endsection
