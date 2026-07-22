@extends('layouts.admin')

@section('title', 'Nuevo aviso')

@section('admin-content')

  <div class="max-w-2xl">
    <h1 class="text-2xl font-semibold tracking-tight">Nuevo aviso</h1>

    <form method="POST" action="{{ route('admin.announcements.store') }}"
          class="mt-8 rounded-card border border-white/10 bg-white/[0.03] p-8 space-y-5">
      @csrf

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Destinatario</label>
        <select name="client_id"
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
          <option value="">Todos los clientes</option>
          @foreach ($clients as $client)
            <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Título *</label>
        <input type="text" name="title" value="{{ old('title') }}" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Mensaje *</label>
        <textarea name="body" rows="5" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition">{{ old('body') }}</textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button class="h-12 rounded-control bg-white px-6 text-sm font-semibold text-black transition hover:bg-zinc-200">
          Publicar aviso
        </button>
        <a href="{{ route('admin.announcements.index') }}"
           class="h-12 flex items-center rounded-control border border-white/15 px-5 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
          Cancelar
        </a>
      </div>
    </form>
  </div>

@endsection
