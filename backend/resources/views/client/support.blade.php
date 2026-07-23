@extends('layouts.client')

@section('title', 'Soporte')

@section('client-content')

  <div>
    <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Ayuda</p>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight">Soporte</h1>
    <p class="mt-1 text-sm text-zinc-400">Escríbenos y te respondemos por correo lo antes posible.</p>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Enviar mensaje</h2>
      <form method="POST" action="{{ route('client.support.store') }}" class="mt-4 space-y-4">
        @csrf

        <div>
          <label for="subject" class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Asunto</label>
          <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="mt-2 w-full rounded-control border border-white/15 bg-white/[0.03] px-3 py-2.5 text-sm text-white placeholder-zinc-500 focus:border-white/30 focus:outline-none" placeholder="¿En qué te ayudamos?">
        </div>

        <div>
          <label for="message" class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Mensaje</label>
          <textarea id="message" name="message" rows="6" class="mt-2 w-full rounded-control border border-white/15 bg-white/[0.03] px-3 py-2.5 text-sm text-white placeholder-zinc-500 focus:border-white/30 focus:outline-none" placeholder="Cuéntanos los detalles">{{ old('message') }}</textarea>
        </div>

        <button type="submit" class="w-full h-11 rounded-control bg-white font-mono text-[11px] uppercase tracking-widest text-zinc-900 transition hover:bg-zinc-200">Enviar</button>
      </form>
    </section>

    <section class="rounded-card border border-white/10 bg-white/[0.03] p-6">
      <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Contacto directo</h2>
      <p class="mt-4 text-sm text-zinc-400">También puedes contactarnos por estos medios:</p>

      <ul class="mt-4 divide-y divide-white/5">
        <li class="py-4">
          <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Correo</p>
          <a href="mailto:cometax@cometax.click" class="mt-1 block text-sm font-medium text-zinc-100 hover:underline underline-offset-4">cometax@cometax.click</a>
        </li>
        <li class="py-4">
          <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">WhatsApp</p>
          <a href="https://wa.me/525532351392" target="_blank" rel="noopener" class="mt-1 block text-sm font-medium text-zinc-100 hover:underline underline-offset-4">+52 55 3235 1392</a>
        </li>
      </ul>
    </section>

  </div>

@endsection
