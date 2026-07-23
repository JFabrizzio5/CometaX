@extends('layouts.client')

@section('title', 'Facturación')

@php
    $statusStyles = [
        'pagado'    => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
        'fallido'   => 'border-red-500/30 bg-red-500/10 text-red-300',
        'pendiente' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
    ];
@endphp

@section('client-content')

  <div>
    <p class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Portal cliente</p>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight">Facturación</h1>
  </div>

  <section class="mt-8 rounded-card border border-white/10 bg-white/[0.03] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-white/10 text-left">
            <th class="px-6 py-4 font-mono text-[10px] uppercase tracking-widest text-zinc-500">Concepto</th>
            <th class="px-6 py-4 font-mono text-[10px] uppercase tracking-widest text-zinc-500">Fecha</th>
            <th class="px-6 py-4 font-mono text-[10px] uppercase tracking-widest text-zinc-500">Método</th>
            <th class="px-6 py-4 font-mono text-[10px] uppercase tracking-widest text-zinc-500 text-right">Importe</th>
            <th class="px-6 py-4 font-mono text-[10px] uppercase tracking-widest text-zinc-500 text-right">Estado</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
          @forelse ($invoices as $invoice)
            <tr class="transition hover:bg-white/[0.02]">
              <td class="px-6 py-4 font-medium text-zinc-100">{{ $invoice->concept }}</td>
              <td class="px-6 py-4 font-mono text-xs text-zinc-400">{{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}</td>
              <td class="px-6 py-4 font-mono text-xs text-zinc-400">{{ $invoice->payment_method_masked ?: '—' }}</td>
              <td class="px-6 py-4 text-right tabular-nums text-zinc-100">${{ number_format($invoice->amount_cents / 100, 2) }}</td>
              <td class="px-6 py-4 text-right">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 font-mono text-[10px] uppercase tracking-widest {{ $statusStyles[$invoice->status] ?? 'border-zinc-500/30 bg-zinc-500/10 text-zinc-400' }}">{{ $invoice->status }}</span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-6 py-12 text-center text-sm text-zinc-500">Aún no hay facturas.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

@endsection
