@extends('layouts.client')

@section('title', 'Calendario')

@php
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $tipos = ['junta_mensual'=>'Junta mensual','soporte_tecnico'=>'Soporte técnico','consultoria_nuevo_proyecto'=>'Nuevo proyecto','revision_contrato'=>'Revisión de contrato'];
    $statusBadge = ['confirmada'=>'text-emerald-300','solicitada'=>'text-sky-300','completada'=>'text-zinc-400','cancelada'=>'text-red-300'];
@endphp

@section('client-content')

  <h1 class="text-2xl font-semibold tracking-tight">Calendario</h1>
  <p class="mt-1 text-sm text-zinc-400">Elige un día y solicita tu reunión.</p>

  <div class="mt-8 grid gap-6 lg:grid-cols-3">

    {{-- Rejilla del mes --}}
    <div class="lg:col-span-2 rounded-card border border-white/10 bg-white/[0.03] p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">{{ $meses[(int) $monthDate->format('n')] }}</p>
          <h2 class="text-xl font-medium">{{ $monthDate->format('Y') }}</h2>
        </div>
        <div class="flex items-center gap-2">
          <a href="{{ route('client.calendar', ['ym' => $prevYm]) }}" class="h-9 w-9 rounded-control border border-white/15 flex items-center justify-center text-zinc-400 hover:text-white hover:border-white/30 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
          </a>
          <a href="{{ route('client.calendar', ['ym' => $nextYm]) }}" class="h-9 w-9 rounded-control border border-white/15 flex items-center justify-center text-zinc-400 hover:text-white hover:border-white/30 transition">
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
                $hasAppt = $cell['appts']->isNotEmpty();
                $label = $cell['day'].' de '.\Illuminate\Support\Str::lower($meses[(int) $monthDate->format('n')]).', '.$monthDate->format('Y');
              @endphp
              @if ($cell['today'])
                <div class="relative aspect-square rounded-control border-2 border-white flex items-center justify-center text-sm font-semibold cursor-pointer"
                     data-date="{{ $cell['date'] }}" data-label="{{ $label }} (hoy)">
                  {{ $cell['day'] }}
                  <span class="absolute bottom-1.5 font-mono text-[8px] uppercase tracking-widest text-zinc-400">hoy</span>
                  @if ($hasAppt)<span class="absolute top-1.5 right-1.5 h-1.5 w-1.5 rounded-full bg-emerald-400"></span>@endif
                </div>
              @elseif ($cell['past'])
                <div class="aspect-square rounded-control border border-white/5 flex items-center justify-center text-sm text-zinc-700 relative">
                  {{ $cell['day'] }}
                  @if ($hasAppt)<span class="absolute top-1.5 right-1.5 h-1.5 w-1.5 rounded-full bg-white/30"></span>@endif
                </div>
              @else
                <div class="relative aspect-square rounded-control flex items-center justify-center text-sm cursor-pointer transition {{ $hasAppt ? 'bg-white/10 border border-white/30' : 'border border-white/15 hover:border-white/40 hover:bg-white/5' }}"
                     data-date="{{ $cell['date'] }}" data-label="{{ $label }}">
                  {{ $cell['day'] }}
                  @if ($hasAppt)<span class="absolute top-1.5 right-1.5 h-1.5 w-1.5 rounded-full bg-emerald-400"></span>@endif
                </div>
              @endif
            @endif
          @endforeach
        @endforeach
      </div>

      <div class="flex flex-wrap items-center gap-5 mt-6 pt-5 border-t border-white/10 font-mono text-[10px] uppercase tracking-widest text-zinc-500">
        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full border-2 border-white"></span>Hoy</span>
        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>Cita agendada</span>
        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full border border-white/15"></span>Disponible</span>
      </div>
    </div>

    {{-- Agendar --}}
    <div class="rounded-card border border-white/10 bg-white/[0.03] p-6 flex flex-col">
      <h2 class="text-base font-semibold mb-4">Agenda una cita</h2>

      <form method="POST" action="{{ route('client.appointments.request') }}" class="space-y-4">
        @csrf
        <input type="hidden" id="appt-date" name="appointment_date" value="{{ old('appointment_date') }}" required>

        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Tipo de reunión</label>
          <select name="meeting_type" required class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            @foreach ($tipos as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
          </select>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Inicio</label>
            <input type="time" name="start_time" required value="{{ old('start_time', '10:00') }}" class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-3 py-3 text-sm outline-none focus:border-white/40 transition">
          </div>
          <div>
            <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Fin</label>
            <input type="time" name="end_time" required value="{{ old('end_time', '10:45') }}" class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-3 py-3 text-sm outline-none focus:border-white/40 transition">
          </div>
        </div>

        <div class="rounded-control border border-white/10 p-4">
          <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mb-1">Fecha seleccionada</p>
          <p id="date-label" class="text-sm text-zinc-300">{{ old('appointment_date') ? \Illuminate\Support\Carbon::parse(old('appointment_date'))->format('d/m/Y') : 'Selecciona un día en el calendario' }}</p>
        </div>

        <textarea name="notes" rows="2" placeholder="Nota (opcional): ¿de qué quieres hablar?" class="w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition">{{ old('notes') }}</textarea>

        <button id="confirm-btn" {{ old('appointment_date') ? '' : 'disabled' }}
                class="w-full flex items-center justify-center gap-2 rounded-full bg-white text-black font-semibold text-sm py-3.5 transition hover:bg-zinc-200 {{ old('appointment_date') ? '' : 'opacity-40 cursor-not-allowed' }}">
          Solicitar cita
        </button>
      </form>

      <div class="mt-8 pt-6 border-t border-white/10">
        <p class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 mb-3">Próximas citas</p>
        <div class="space-y-2">
          @forelse ($proximas as $c)
            <div class="rounded-control border border-white/10 p-3 flex items-center gap-3">
              <div class="h-10 w-10 rounded-control bg-white/5 border border-white/10 flex flex-col items-center justify-center shrink-0">
                <span class="font-mono text-[9px] uppercase text-zinc-500 leading-none">{{ $c->appointment_date->format('M') }}</span>
                <span class="text-sm font-semibold leading-none mt-0.5">{{ $c->appointment_date->format('d') }}</span>
              </div>
              <div class="min-w-0">
                <p class="text-sm truncate">{{ str_replace('_', ' ', $c->meeting_type) }}</p>
                <p class="text-xs text-zinc-500">{{ substr($c->start_time, 0, 5) }}–{{ substr($c->end_time, 0, 5) }} · <span class="{{ $statusBadge[$c->status] ?? 'text-zinc-400' }}">{{ $c->status }}</span></p>
              </div>
            </div>
          @empty
            <p class="rounded-control border border-white/10 p-4 text-center text-sm text-zinc-500">Sin citas próximas.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll('[data-date]').forEach(function (cell) {
      cell.addEventListener('click', function () {
        document.querySelectorAll('[data-date]').forEach(function (c) { c.classList.remove('ring-2', 'ring-white'); });
        cell.classList.add('ring-2', 'ring-white');
        document.getElementById('appt-date').value = cell.getAttribute('data-date');
        document.getElementById('date-label').textContent = cell.getAttribute('data-label');
        var btn = document.getElementById('confirm-btn');
        btn.disabled = false;
        btn.classList.remove('opacity-40', 'cursor-not-allowed');
      });
    });
  </script>

@endsection
