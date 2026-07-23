@extends('layouts.client')

@section('title', 'Contratos')

@php
    $statusStyles = [
        'firmado'    => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'vigente'    => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'por_renovar' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
        'vencido'    => 'border-red-500/30 bg-red-500/10 text-red-300',
    ];
@endphp

@section('client-content')

  <div>
    <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Portal cliente</p>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight">Contratos</h1>
  </div>

  <section class="mt-8">
    <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Contratos</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
      @forelse ($contracts as $contract)
        <div class="rounded-card border border-white/10 bg-white/[0.03] p-6">
          <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-medium text-zinc-100">{{ $contract->title }}</p>
            <span class="shrink-0 inline-flex items-center rounded-full border px-2.5 py-1 font-mono text-[10px] uppercase tracking-widest {{ $statusStyles[$contract->status] ?? 'border-zinc-500/30 bg-zinc-500/10 text-zinc-400' }}">{{ $contract->status ?? 'sin estado' }}</span>
          </div>

          <dl class="mt-4 space-y-1.5 font-mono text-xs text-zinc-500">
            @if ($contract->signed_date)
              <div class="flex justify-between gap-3"><dt>Firmado</dt><dd class="text-zinc-300">{{ $contract->signed_date?->format('d/m/Y') }}</dd></div>
            @endif
            @if ($contract->renewal_date)
              <div class="flex justify-between gap-3"><dt>Renovación</dt><dd class="text-zinc-300">{{ $contract->renewal_date?->format('d/m/Y') }}</dd></div>
            @endif
            @if ($contract->term_length)
              <div class="flex justify-between gap-3"><dt>Vigencia</dt><dd class="text-zinc-300">{{ $contract->term_length }}</dd></div>
            @endif
            @if ($contract->version)
              <div class="flex justify-between gap-3"><dt>Versión</dt><dd class="text-zinc-300">{{ $contract->version }}</dd></div>
            @endif
          </dl>

          @if ($contract->file_path && str_starts_with($contract->file_path, 'http'))
            <a href="{{ $contract->file_path }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center font-mono text-[10px] uppercase tracking-widest text-zinc-400 transition hover:text-white">Ver documento →</a>
          @endif
        </div>
      @empty
        <div class="sm:col-span-2 rounded-card border border-white/10 bg-white/[0.03] p-8 text-center text-sm text-zinc-500">Aún no hay contratos.</div>
      @endforelse
    </div>
  </section>

  <section class="mt-8">
    <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Otros documentos</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
      @forelse ($documents as $document)
        <div class="rounded-card border border-white/10 bg-white/[0.03] p-6">
          <p class="text-sm font-medium text-zinc-100">{{ $document->title }}</p>

          <dl class="mt-4 space-y-1.5 font-mono text-xs text-zinc-500">
            @if ($document->signed_date)
              <div class="flex justify-between gap-3"><dt>Firmado</dt><dd class="text-zinc-300">{{ $document->signed_date?->format('d/m/Y') }}</dd></div>
            @endif
            @if ($document->renewal_date)
              <div class="flex justify-between gap-3"><dt>Renovación</dt><dd class="text-zinc-300">{{ $document->renewal_date?->format('d/m/Y') }}</dd></div>
            @endif
            @if ($document->term_length)
              <div class="flex justify-between gap-3"><dt>Vigencia</dt><dd class="text-zinc-300">{{ $document->term_length }}</dd></div>
            @endif
            @if ($document->version)
              <div class="flex justify-between gap-3"><dt>Versión</dt><dd class="text-zinc-300">{{ $document->version }}</dd></div>
            @endif
          </dl>

          @if ($document->file_path && str_starts_with($document->file_path, 'http'))
            <a href="{{ $document->file_path }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center font-mono text-[10px] uppercase tracking-widest text-zinc-400 transition hover:text-white">Ver documento →</a>
          @endif
        </div>
      @empty
        <div class="sm:col-span-2 rounded-card border border-white/10 bg-white/[0.03] p-8 text-center text-sm text-zinc-500">Aún no hay documentos.</div>
      @endforelse
    </div>
  </section>

@endsection
