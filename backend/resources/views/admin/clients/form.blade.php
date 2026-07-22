@extends('layouts.admin')

@section('title', $client->exists ? 'Editar cliente' : 'Nuevo cliente')

@section('admin-content')

  <div class="max-w-2xl">
    <h1 class="text-2xl font-semibold tracking-tight">{{ $client->exists ? "Editar «{$client->name}»" : 'Nuevo cliente' }}</h1>

    <form method="POST"
          action="{{ $client->exists ? route('admin.clients.update', $client) : route('admin.clients.store') }}"
          class="mt-8 rounded-card border border-white/10 bg-white/[0.03] p-8 space-y-5">
      @csrf
      @if ($client->exists) @method('PUT') @endif

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Nombre / razón social *</label>
        <input type="text" name="name" value="{{ old('name', $client->name) }}" required
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Correo de contacto</label>
          <input type="email" name="contact_email" value="{{ old('contact_email', $client->contact_email) }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Teléfono</label>
          <input type="text" name="contact_phone" value="{{ old('contact_phone', $client->contact_phone) }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">RFC</label>
          <input type="text" name="rfc" value="{{ old('rfc', $client->rfc) }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Plan</label>
          <select name="plan_id"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            <option value="">Sin plan</option>
            @foreach ($plans as $plan)
              <option value="{{ $plan->id }}" @selected(old('plan_id', $client->plan_id) == $plan->id)>{{ $plan->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Dirección</label>
        <textarea name="address" rows="2"
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition">{{ old('address', $client->address) }}</textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button class="h-12 rounded-control bg-white px-6 text-sm font-semibold text-black transition hover:bg-zinc-200">
          {{ $client->exists ? 'Guardar cambios' : 'Crear cliente' }}
        </button>
        <a href="{{ $client->exists ? route('admin.clients.show', $client) : route('admin.clients.index') }}"
           class="h-12 flex items-center rounded-control border border-white/15 px-5 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
          Cancelar
        </a>
      </div>
    </form>
  </div>

@endsection
