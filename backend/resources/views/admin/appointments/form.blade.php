@extends('layouts.admin')

@section('title', $appointment->exists ? 'Editar cita' : 'Nueva cita')

@php
    $tipos = [
        'junta_mensual' => 'Junta mensual',
        'soporte_tecnico' => 'Soporte técnico',
        'consultoria_nuevo_proyecto' => 'Consultoría de nuevo proyecto',
        'revision_contrato' => 'Revisión de contrato',
        'asesoria_publica' => 'Asesoría pública',
    ];
    $estados = [
        'solicitada' => 'Solicitada',
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
        'completada' => 'Completada',
    ];
@endphp

@section('admin-content')

  <div class="max-w-2xl">
    <h1 class="text-2xl font-semibold tracking-tight">{{ $appointment->exists ? 'Editar cita' : 'Nueva cita' }}</h1>

    <form method="POST"
          action="{{ $appointment->exists ? route('admin.appointments.update', $appointment) : route('admin.appointments.store') }}"
          class="mt-8 rounded-card border border-white/10 bg-white/[0.03] p-8 space-y-5">
      @csrf
      @if ($appointment->exists) @method('PUT') @endif

      @if ($appointment->lead_id !== null)
        {{-- Cita de prospecto: el lead es el destinatario. Elegir cliente la convierte. --}}
        <div class="rounded-control border border-sky-500/30 bg-sky-500/10 px-4 py-3 text-sm text-sky-200">
          Cita de prospecto: <span class="font-medium">{{ $appointment->lead?->name ?? 'Lead #'.$appointment->lead_id }}</span>
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Convertir a cliente (opcional)</label>
          <select name="client_id"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            <option value="">Mantener como prospecto</option>
            @foreach ($clients as $client)
              <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>
            @endforeach
          </select>
        </div>
      @else
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Cliente *</label>
          <select name="client_id" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            <option value="">Selecciona un cliente</option>
            @foreach ($clients as $client)
              <option value="{{ $client->id }}" @selected(old('client_id', $appointment->client_id) == $client->id)>{{ $client->name }}</option>
            @endforeach
          </select>
        </div>
      @endif

      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Tipo de reunión *</label>
          <select name="meeting_type" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            @foreach ($tipos as $value => $label)
              <option value="{{ $value }}" @selected(old('meeting_type', $appointment->meeting_type) === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Estado *</label>
          <select name="status" required
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
            @foreach ($estados as $value => $label)
              <option value="{{ $value }}" @selected(old('status', $appointment->status) === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="grid gap-5 sm:grid-cols-3">
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Fecha *</label>
          <input type="date" name="appointment_date" required
            value="{{ old('appointment_date', $appointment->appointment_date?->format('Y-m-d')) }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Hora inicio *</label>
          <input type="time" name="start_time" required
            value="{{ old('start_time', $appointment->start_time ? substr($appointment->start_time, 0, 5) : '') }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
        <div>
          <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Hora fin *</label>
          <input type="time" name="end_time" required
            value="{{ old('end_time', $appointment->end_time ? substr($appointment->end_time, 0, 5) : '') }}"
            class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
        </div>
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Lugar / link de reunión</label>
        <input type="text" name="location" value="{{ old('location', $appointment->location) }}"
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition" />
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Consultor</label>
        <select name="consultant_id"
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition [&>option]:bg-zinc-900">
          <option value="">Sin asignar</option>
          @foreach ($consultants as $consultant)
            <option value="{{ $consultant->id }}" @selected(old('consultant_id', $appointment->consultant_id) == $consultant->id)>{{ $consultant->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="font-mono text-[10px] uppercase tracking-widest text-zinc-500">Notas</label>
        <textarea name="notes" rows="3"
          class="mt-2 w-full rounded-control bg-white/5 border border-white/15 px-4 py-3 text-sm outline-none focus:border-white/40 transition">{{ old('notes', $appointment->notes) }}</textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button class="h-12 rounded-control bg-white px-6 text-sm font-semibold text-black transition hover:bg-zinc-200">
          {{ $appointment->exists ? 'Guardar cambios' : 'Crear cita' }}
        </button>
        <a href="{{ route('admin.appointments.index') }}"
           class="h-12 flex items-center rounded-control border border-white/15 px-5 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white">
          Cancelar
        </a>
      </div>
    </form>
  </div>

@endsection
