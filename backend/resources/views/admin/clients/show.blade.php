@extends('layouts.admin')

@section('title', $client->name)

@php
    $money = fn (int $cents) => '$' . number_format($cents / 100, 2);
@endphp

@section('admin-content')

  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Cliente</p>
      <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $client->name }}</h1>
      <p class="mt-1 font-mono text-xs text-zinc-500">
        {{ $client->contact_email ?? 'sin correo' }} · {{ $client->contact_phone ?? 'sin teléfono' }} · Plan: {{ $client->plan?->name ?? 'sin plan' }}
      </p>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('admin.projects.create', ['client' => $client->id]) }}"
         class="h-11 flex items-center rounded-control bg-white px-4 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">
        + Proyecto
      </a>
      <a href="{{ route('admin.clients.edit', $client) }}"
         class="h-11 flex items-center rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
        Editar
      </a>
    </div>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Proyectos</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($client->projects as $project)
          <li class="py-3 flex items-center justify-between gap-4">
            <div>
              <a href="{{ route('admin.projects.show', $project) }}" class="text-sm font-medium hover:underline underline-offset-4">{{ $project->name }}</a>
              <p class="font-mono text-xs text-zinc-500">{{ $project->status }} · {{ $project->progress_percent }}% · {{ $project->hours_used }}/{{ $project->hours_budgeted }}h</p>
            </div>
            <span class="font-mono text-xs text-zinc-400">{{ $project->created_at->format('d/m/Y') }}</span>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin proyectos todavía.</li>
        @endforelse
      </ul>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Usuarios con acceso</h2>
      <ul class="mt-4 divide-y divide-white/5">
        @forelse ($client->users as $user)
          <li class="py-3 flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-medium">{{ $user->name }}</p>
              <p class="font-mono text-xs text-zinc-500">{{ $user->email }}</p>
            </div>
            <span class="font-mono text-[10px] uppercase tracking-widest {{ $user->role === 'admin' ? 'text-emerald-300' : 'text-zinc-400' }}">{{ $user->role }}</span>
          </li>
        @empty
          <li class="py-6 text-center text-sm text-zinc-500">Sin usuarios todavía.</li>
        @endforelse
      </ul>

      <form method="POST" action="{{ route('admin.clients.users.store', $client) }}" class="mt-5 border-t border-white/10 pt-5 space-y-3">
        @csrf
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Agregar usuario</p>
        <div class="grid gap-3 sm:grid-cols-2">
          <input type="text" name="name" placeholder="Nombre" required
            class="h-11 rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition" />
          <input type="email" name="email" placeholder="correo@cliente.com" required
            class="h-11 rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition" />
        </div>
        <div class="flex items-center gap-3">
          <select name="role" class="h-11 rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            <option value="member">Miembro</option>
            <option value="admin">Admin del cliente</option>
          </select>
          <button class="h-11 rounded-control border border-white/15 px-5 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
            Agregar
          </button>
        </div>
      </form>
    </section>

  </div>

  <section class="mt-6 rounded-card border border-white/10 bg-white/[0.03] p-6">
    <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Facturas recientes</h2>
    <ul class="mt-4 divide-y divide-white/5">
      @forelse ($client->invoices as $invoice)
        <li class="py-3 flex items-center justify-between gap-4">
          <div>
            <p class="text-sm font-medium">{{ $invoice->concept }}</p>
            <p class="font-mono text-xs text-zinc-500">{{ $invoice->invoice_date->format('d/m/Y') }}</p>
          </div>
          <div class="text-right">
            <p class="font-mono text-sm">{{ $money($invoice->amount_cents) }}</p>
            <p class="font-mono text-[10px] uppercase tracking-widest {{ $invoice->status === 'pagado' ? 'text-emerald-300' : ($invoice->status === 'fallido' ? 'text-red-300' : 'text-amber-300') }}">{{ $invoice->status }}</p>
          </div>
        </li>
      @empty
        <li class="py-6 text-center text-sm text-zinc-500">Sin facturas todavía.</li>
      @endforelse
    </ul>
  </section>

@endsection
