@extends('layouts.admin')

@section('title', 'Calendario')

@php
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $stBadge = ['confirmada'=>'text-emerald-300','solicitada'=>'text-sky-300','completada'=>'text-zinc-400','cancelada'=>'text-red-300'];
@endphp

@section('admin-content')

  <div class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Calendario</h1>
      <p class="mt-1 text-sm text-zinc-400">Citas de todos los clientes. Bloquea días u horarios como ocupados.</p>
    </div>
    <a href="{{ route('admin.appointments.create') }}" class="h-11 flex items-center rounded-control bg-white px-5 font-mono text-xs uppercase tracking-widest text-black transition hover:bg-zinc-200">+ Nueva cita</a>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-3">

    {{-- Rejilla del mes --}}
    <div class="lg:col-span-2 rounded-card border border-white/10 bg-white/[0.03] p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{{ $meses[(int) $monthDate->format('n')] }}</p>
          <h2 class="text-xl font-medium">{{ $monthDate->format('Y') }}</h2>
        </div>
        <div class="flex items-center gap-2">
          <a href="{{ route('admin.appointments.calendar', ['ym' => $prevYm]) }}" class="h-9 w-9 rounded-control border border-white/15 flex items-center justify-center text-zinc-400 hover:text-white hover:border-white/30 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
          </a>
          <a href="{{ route('admin.appointments.calendar', ['ym' => $nextYm]) }}" class="h-9 w-9 rounded-control border border-white/15 flex items-center justify-center text-zinc-400 hover:text-white hover:border-white/30 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
          </a>
        </div>
      </div>

      <div class="grid grid-cols-7 gap-1.5 mb-2">
        @foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $wd)
          <div class="text-center font-mono text-[10px] uppercase tracking-widest text-zinc-600 py-2">{{ $wd }}</div>
        @endforeach
      </div>

      <div class="grid grid-cols-7 gap-1.5">
        @foreach ($weeks as $week)
          @foreach ($week as $cell)
            @if ($cell === null)
              <div></div>
            @else
              @php
                $hasCita = $cell['citas']->isNotEmpty();
                $blocked = $cell['bloqueos']->firstWhere('all_day', true);
                $hasBlock = $cell['bloqueos']->isNotEmpty();
              @endphp
              <a href="{{ route('admin.appointments.calendar', ['ym' => $monthDate->format('Y-m'), 'dia' => $cell['date']]) }}"
                 class="relative aspect-square rounded-control flex flex-col items-center justify-center text-sm transition
                        {{ $cell['selected'] ? 'ring-2 ring-white' : '' }}
                        {{ $cell['today'] ? 'border-2 border-white font-semibold' : ($blocked ? 'border border-red-400/40 bg-red-500/10' : ($cell['past'] ? 'border border-white/5 text-zinc-600' : 'border border-white/15 hover:border-white/40 hover:bg-white/5')) }}">
                {{ $cell['day'] }}
                <span class="absolute top-1.5 right-1.5 flex gap-0.5">
                  @if ($hasCita)<span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>@endif
                  @if ($hasBlock)<span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>@endif
                </span>
                @if ($cell['today'])<span class="absolute bottom-1 font-mono text-[8px] uppercase tracking-widest text-zinc-400">hoy</span>@endif
              </a>
            @endif
          @endforeach
        @endforeach
      </div>

      <div class="flex flex-wrap items-center gap-5 mt-6 pt-5 border-t border-white/10 font-mono text-[10px] uppercase tracking-widest text-zinc-500">
        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full border-2 border-white"></span>Hoy</span>
        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>Con cita</span>
        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-400"></span>Bloqueado</span>
      </div>
    </div>

    {{-- Panel del día seleccionado --}}
    <div class="rounded-card border border-white/10 bg-white/[0.03] p-6 flex flex-col">
      <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Día seleccionado</p>
      <h2 class="mt-1 text-base font-semibold">{{ $selected->format('d') }} de {{ \Illuminate\Support\Str::lower($meses[(int) $selected->format('n')]) }}, {{ $selected->format('Y') }}</h2>

      {{-- Citas del día --}}
      <div class="mt-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mb-2">Citas</p>
        <div class="space-y-2">
          @forelse ($citasDia as $c)
            <div class="rounded-control border border-white/10 p-3">
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium truncate">{{ $c->client?->name ?? $c->lead?->name ?? 'Prospecto' }}</p>
                <span class="font-mono text-[10px] uppercase tracking-widest {{ $stBadge[$c->status] ?? 'text-zinc-400' }}">{{ $c->status }}</span>
              </div>
              <p class="font-mono text-xs text-zinc-500 mt-0.5">{{ substr($c->start_time, 0, 5) }}–{{ substr($c->end_time, 0, 5) }} · {{ str_replace('_', ' ', $c->meeting_type) }}</p>
              <div class="mt-2 flex gap-2">
                @if ($c->status !== 'confirmada')
                  <form method="POST" action="{{ route('admin.appointments.confirm', $c) }}">@csrf
                    <button class="font-mono text-[10px] uppercase tracking-widest text-emerald-300 hover:text-emerald-200">Confirmar</button>
                  </form>
                @endif
                @if ($c->status !== 'cancelada')
                  <form method="POST" action="{{ route('admin.appointments.cancel', $c) }}">@csrf
                    <button class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-red-300">Cancelar</button>
                  </form>
                @endif
                <a href="{{ route('admin.appointments.edit', $c) }}" class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-white">Editar</a>
              </div>
            </div>
          @empty
            <p class="rounded-control border border-white/10 p-3 text-center text-sm text-zinc-500">Sin citas este día.</p>
          @endforelse
        </div>
      </div>

      {{-- Bloqueos del día --}}
      <div class="mt-5">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mb-2">Bloqueos</p>
        <div class="space-y-2">
          @forelse ($bloqueosDia as $b)
            <div class="rounded-control border border-red-400/20 bg-red-500/5 p-3 flex items-center justify-between gap-2">
              <div class="min-w-0">
                <p class="text-sm">{{ $b->all_day ? 'Día completo' : substr($b->start_time, 0, 5).'–'.substr($b->end_time, 0, 5) }}</p>
                @if ($b->reason)<p class="text-xs text-zinc-500 truncate">{{ $b->reason }}</p>@endif
              </div>
              <form method="POST" action="{{ route('admin.blocks.destroy', $b) }}">@csrf @method('DELETE')
                <button class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 hover:text-red-300 shrink-0">Quitar</button>
              </form>
            </div>
          @empty
            <p class="rounded-control border border-white/10 p-3 text-center text-sm text-zinc-500">Sin bloqueos.</p>
          @endforelse
        </div>
      </div>

      {{-- Bloquear --}}
      <form method="POST" action="{{ route('admin.blocks.store') }}" class="mt-5 border-t border-white/10 pt-5 space-y-3">
        @csrf
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Marcar como ocupado</p>
        <input type="hidden" name="date" value="{{ $selected->toDateString() }}">
        <label class="flex items-center gap-2 text-sm text-zinc-300">
          <input type="checkbox" id="allday" name="all_day" value="1" checked onchange="document.getElementById('slot').classList.toggle('hidden', this.checked)" class="h-3.5 w-3.5 rounded-[4px] bg-white/5 border border-white/20 accent-white">
          Día completo
        </label>
        <div id="slot" class="hidden grid grid-cols-2 gap-2">
          <input type="time" name="start_time" class="h-11 rounded-control bg-white/5 border border-white/15 px-3 text-sm outline-none focus:border-white/40 transition">
          <input type="time" name="end_time" class="h-11 rounded-control bg-white/5 border border-white/15 px-3 text-sm outline-none focus:border-white/40 transition">
        </div>
        <input type="text" name="reason" placeholder="Motivo (opcional)" class="w-full h-11 rounded-control bg-white/5 border border-white/15 px-4 text-sm outline-none focus:border-white/40 transition">
        <button class="h-11 w-full rounded-control border border-red-400/40 bg-red-500/10 text-sm font-medium text-red-200 transition hover:bg-red-500/20">Bloquear</button>
      </form>
    </div>
  </div>

@endsection
