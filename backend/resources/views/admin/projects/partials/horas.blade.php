@php
    use App\Models\TimeEntry;
    use App\Services\DesgloseHoras;

    // $entries ya viene filtrado desde el controlador; $resumen se calcula sobre
    // ese mismo conjunto para que los totales no contradigan a la tabla.
    $totalHoras = $resumen['total'];
    $filtrando = $totalHoras !== $totalSinFiltrar;

    // Los lotes reconstruidos se deshacen completos: se cuentan aparte para
    // ofrecer el botón una sola vez por lote.
    $lotes = $entries->whereNotNull('batch_id')->groupBy('batch_id');

    $desglose = session('desglose');
    $desgloseEsDeEsteProyecto = session('desgloseProyecto') === $project->id;

    $inputClass = 'w-full rounded-control bg-white/5 border border-white/15 px-3 py-2 text-sm outline-none focus:border-white/40 transition';
    $selectClass = $inputClass.' [&>option]:bg-zinc-900';
    $labelClass = 'font-mono text-[10px] uppercase tracking-widest text-zinc-500';
    $btnClass = 'h-11 shrink-0 rounded-control border border-white/15 px-4 font-mono text-xs uppercase tracking-widest text-zinc-300 transition hover:border-white/30 hover:text-white';
@endphp

<section class="mt-6 rounded-card border border-white/10 bg-white/[0.03] p-6">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <h2 class="font-mono text-[11px] uppercase tracking-widest text-zinc-500">Registro de horas</h2>
    <p class="font-mono text-xs text-zinc-500">
      {{ number_format($totalHoras, 2) }} h
      @if ($filtrando)
        <span class="text-amber-300">filtradas</span> de {{ number_format($totalSinFiltrar, 2) }} h
      @else
        registradas
      @endif
      @if ($project->hours_budgeted > 0)
        · {{ number_format($project->hours_budgeted, 2) }} h presupuestadas
      @endif
    </p>
  </div>

  {{-- Propuesta pendiente de revisión: se edita aquí y solo se guarda al confirmar. --}}
  @if ($desglose && $desgloseEsDeEsteProyecto)
    <form method="POST" action="{{ route('admin.time.confirm', $project) }}"
          class="mt-5 rounded-card border border-amber-500/30 bg-amber-500/[0.06] p-5">
      @csrf
      <p class="font-mono text-[11px] uppercase tracking-widest text-amber-300">Propuesta de desglose — revisa antes de guardar</p>
      <p class="mt-2 text-xs text-zinc-400">
        Reparto sugerido del trabajo que declaraste. Ajusta horas, redacción y responsable;
        borra lo que no aplique. Se guardará marcado como reconstruido y podrás deshacerlo completo.
      </p>

      <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[820px] text-sm">
          <thead>
            <tr class="text-left {{ $labelClass }}">
              <th class="pb-2 pr-3">Fecha</th>
              <th class="pb-2 pr-3">Actividad</th>
              <th class="pb-2 pr-3">Tipo</th>
              <th class="pb-2 pr-3">Quién</th>
              <th class="pb-2 pr-3">Hito</th>
              <th class="pb-2 pr-3 text-right">Horas</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            @foreach ($desglose as $i => $renglon)
              <tr>
                <td class="py-2 pr-3">
                  <input type="date" name="renglones[{{ $i }}][entry_date]" value="{{ $renglon['entry_date'] }}" required class="{{ $inputClass }}" />
                </td>
                <td class="py-2 pr-3">
                  <input type="text" name="renglones[{{ $i }}][activity]" value="{{ $renglon['activity'] }}" required maxlength="255" class="{{ $inputClass }}" />
                </td>
                <td class="py-2 pr-3">
                  <select name="renglones[{{ $i }}][category]" class="{{ $selectClass }}">
                    @foreach (TimeEntry::CATEGORIAS as $valor => $etiqueta)
                      <option value="{{ $valor }}" @selected($renglon['category'] === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                  </select>
                </td>
                <td class="py-2 pr-3">
                  <select name="renglones[{{ $i }}][consultant_id]" class="{{ $selectClass }}">
                    <option value="">Sin asignar</option>
                    @foreach ($consultants as $consultant)
                      <option value="{{ $consultant->id }}" @selected(($renglon['consultant_id'] ?? null) === $consultant->id)>{{ $consultant->name }}</option>
                    @endforeach
                  </select>
                </td>
                <td class="py-2 pr-3">
                  <select name="renglones[{{ $i }}][milestone_id]" class="{{ $selectClass }}">
                    <option value="">Sin hito</option>
                    @foreach ($project->milestones as $milestone)
                      <option value="{{ $milestone->id }}" @selected(($renglon['milestone_id'] ?? null) === $milestone->id)>{{ $milestone->label }}</option>
                    @endforeach
                  </select>
                </td>
                <td class="py-2 pr-3">
                  <input type="number" name="renglones[{{ $i }}][hours]" value="{{ $renglon['hours'] }}" min="0.25" step="0.25" required class="{{ $inputClass }} text-right" />
                </td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="5" class="pt-3 text-right {{ $labelClass }}">Total propuesto</td>
              <td class="pt-3 pr-3 text-right font-mono text-sm">{{ number_format(collect($desglose)->sum('hours'), 2) }} h</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="mt-4 flex flex-wrap gap-2 border-t border-white/10 pt-4">
        <button class="h-11 shrink-0 rounded-control border border-amber-400/40 bg-amber-400/10 px-4 font-mono text-xs uppercase tracking-widest text-amber-200 transition hover:border-amber-300/60 hover:text-amber-100">
          Guardar desglose
        </button>
        <a href="{{ route('admin.projects.show', $project) }}" class="{{ $btnClass }} flex items-center">Descartar</a>
      </div>
    </form>
  @endif

  <div class="mt-6 grid gap-6 lg:grid-cols-2">

    {{-- Alta manual: una hora que sí se registró en el momento. --}}
    <form method="POST" action="{{ route('admin.time.store', $project) }}" class="space-y-3">
      @csrf
      <p class="{{ $labelClass }}">Registrar horas</p>

      <div class="grid gap-3 sm:grid-cols-2">
        <div>
          <label class="{{ $labelClass }}">Fecha</label>
          <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required class="mt-2 {{ $inputClass }}" />
        </div>
        <div>
          <label class="{{ $labelClass }}">Horas</label>
          <input type="number" name="hours" value="{{ old('hours') }}" min="0.25" step="0.25" required class="mt-2 {{ $inputClass }}" />
        </div>
      </div>

      <div>
        <label class="{{ $labelClass }}">Actividad</label>
        <input type="text" name="activity" value="{{ old('activity') }}" required maxlength="255"
               placeholder="Qué se hizo" class="mt-2 {{ $inputClass }}" />
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <div>
          <label class="{{ $labelClass }}">Tipo</label>
          <select name="category" class="mt-2 {{ $selectClass }}">
            @foreach (TimeEntry::CATEGORIAS as $valor => $etiqueta)
              <option value="{{ $valor }}" @selected(old('category') === $valor)>{{ $etiqueta }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="{{ $labelClass }}">Quién</label>
          <select name="consultant_id" class="mt-2 {{ $selectClass }}">
            <option value="">Sin asignar</option>
            @foreach ($consultants as $consultant)
              <option value="{{ $consultant->id }}" @selected((int) old('consultant_id') === $consultant->id)>{{ $consultant->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div>
        <label class="{{ $labelClass }}">Hito</label>
        <select name="milestone_id" class="mt-2 {{ $selectClass }}">
          <option value="">Sin hito</option>
          @foreach ($project->milestones as $milestone)
            <option value="{{ $milestone->id }}" @selected((int) old('milestone_id') === $milestone->id)>{{ $milestone->label }}</option>
          @endforeach
        </select>
      </div>

      <button class="{{ $btnClass }}">+ Registrar</button>
    </form>

    {{-- Reconstrucción: trabajo real que no se alcanzó a registrar. --}}
    <form method="POST" action="{{ route('admin.time.propose', $project) }}" class="space-y-3">
      @csrf
      <p class="{{ $labelClass }}">Reconstruir un trabajo ya entregado</p>
      <p class="text-xs text-zinc-500">
        Describe lo que se entregó y te propone el desglose por actividad. Nada se guarda hasta que lo revises.
      </p>

      <div>
        <label class="{{ $labelClass }}">Qué se entregó</label>
        <input type="text" name="titulo" value="{{ old('titulo') }}" required maxlength="255"
               placeholder="ej. Exportador de reportes" class="mt-2 {{ $inputClass }}" />
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <div>
          <label class="{{ $labelClass }}">Tipo de entrega</label>
          <select name="tipo" class="mt-2 {{ $selectClass }}">
            @foreach (DesgloseHoras::TIPOS as $valor => $etiqueta)
              <option value="{{ $valor }}" @selected(old('tipo') === $valor)>{{ $etiqueta }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="{{ $labelClass }}">Horas aproximadas</label>
          <input type="number" name="horas" value="{{ old('horas') }}" min="0.25" step="0.25" required class="mt-2 {{ $inputClass }}" />
        </div>
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <div>
          <label class="{{ $labelClass }}">Desde</label>
          <input type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" required class="mt-2 {{ $inputClass }}" />
        </div>
        <div>
          <label class="{{ $labelClass }}">Hasta (opcional)</label>
          <input type="date" name="fecha_fin" value="{{ old('fecha_fin') }}" class="mt-2 {{ $inputClass }}" />
        </div>
      </div>

      <div>
        <label class="{{ $labelClass }}">Hito al que pertenece</label>
        <select name="milestone_id" class="mt-2 {{ $selectClass }}">
          <option value="">Sin hito</option>
          @foreach ($project->milestones as $milestone)
            <option value="{{ $milestone->id }}" @selected((int) old('milestone_id') === $milestone->id)>{{ $milestone->label }}</option>
          @endforeach
        </select>
      </div>

      <label class="flex items-start gap-3 text-xs text-zinc-400">
        <input type="checkbox" name="incluir_qa_automatizado" value="1" class="mt-0.5 h-4 w-4 rounded border-white/20 bg-white/5" />
        <span>Agregar el renglón de suite automatizada (solo si de verdad corrió en CI)</span>
      </label>

      <button class="{{ $btnClass }}">Proponer desglose</button>
    </form>

  </div>

  {{-- Filtros: viajan por GET para poder compartir la vista filtrada. --}}
  <form method="GET" action="{{ route('admin.projects.show', $project) }}" class="mt-6 border-t border-white/10 pt-5">
    <p class="{{ $labelClass }}">Filtrar</p>
    <div class="mt-3 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
      <div>
        <label class="{{ $labelClass }}">Desde</label>
        <input type="date" name="desde" value="{{ $filtros['desde'] }}" class="mt-1 {{ $inputClass }}" />
      </div>
      <div>
        <label class="{{ $labelClass }}">Hasta</label>
        <input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="mt-1 {{ $inputClass }}" />
      </div>
      <div>
        <label class="{{ $labelClass }}">Hito</label>
        <select name="hito" class="mt-1 {{ $selectClass }}">
          <option value="">Todos</option>
          @foreach ($project->milestones as $milestone)
            <option value="{{ $milestone->id }}" @selected((string) $filtros['hito'] === (string) $milestone->id)>{{ $milestone->label }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="{{ $labelClass }}">Tipo</label>
        <select name="categoria" class="mt-1 {{ $selectClass }}">
          <option value="">Todos</option>
          @foreach (TimeEntry::CATEGORIAS as $valor => $etiqueta)
            <option value="{{ $valor }}" @selected($filtros['categoria'] === $valor)>{{ $etiqueta }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="{{ $labelClass }}">Quién</label>
        <select name="quien" class="mt-1 {{ $selectClass }}">
          <option value="">Todos</option>
          @foreach ($consultants as $consultant)
            <option value="{{ $consultant->id }}" @selected((string) $filtros['quien'] === (string) $consultant->id)>{{ $consultant->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="{{ $labelClass }}">Orden</label>
        <select name="orden" class="mt-1 {{ $selectClass }}">
          <option value="desc" @selected($filtros['orden'] === 'desc')>Más reciente primero</option>
          <option value="asc" @selected($filtros['orden'] === 'asc')>Cronológico</option>
        </select>
      </div>
    </div>
    <div class="mt-3 flex gap-2">
      <button class="{{ $btnClass }}">Aplicar</button>
      @if ($filtrando || $filtros['orden'] === 'asc')
        <a href="{{ route('admin.projects.show', $project) }}" class="{{ $btnClass }} flex items-center">Limpiar</a>
      @endif
    </div>
  </form>

  {{-- Análisis: en qué se va el tiempo, sobre el conjunto filtrado. --}}
  @if ($entries->isNotEmpty())
    <div class="mt-6 grid gap-6 border-t border-white/10 pt-5 lg:grid-cols-3">
      @foreach ([
        'Por tipo de trabajo' => $resumen['por_categoria'],
        'Por persona' => $resumen['por_persona'],
        'Por fase / sprint' => $resumen['por_fase'],
      ] as $titulo => $grupos)
        <div>
          <p class="{{ $labelClass }}">{{ $titulo }}</p>
          <ul class="mt-3 space-y-2">
            @foreach ($grupos as $grupo)
              <li>
                <div class="flex items-baseline justify-between gap-3 text-sm">
                  <span class="truncate">{{ $grupo['etiqueta'] }}</span>
                  <span class="shrink-0 font-mono text-xs text-zinc-400">
                    {{ number_format($grupo['horas'], 2) }} h · {{ $grupo['porcentaje'] }}%
                  </span>
                </div>
                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-white/5">
                  <div class="h-full rounded-full bg-white/40" style="width: {{ $grupo['porcentaje'] }}%"></div>
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Lo ya registrado --}}
  <div class="mt-6 border-t border-white/10 pt-5">
    @if ($lotes->isNotEmpty())
      <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="{{ $labelClass }}">Lotes reconstruidos:</span>
        @foreach ($lotes as $batch => $renglones)
          <form method="POST" action="{{ route('admin.time.batch.destroy', [$project, $batch]) }}"
                onsubmit="return confirm('¿Deshacer el lote completo ({{ $renglones->count() }} renglones)?')">
            @csrf
            @method('DELETE')
            <button class="rounded-control border border-white/15 px-3 py-1 font-mono text-[10px] uppercase tracking-widest text-zinc-400 transition hover:border-red-400/40 hover:text-red-300">
              {{ $renglones->first()->entry_date->format('d/m/Y') }} · {{ $renglones->count() }} renglones · deshacer
            </button>
          </form>
        @endforeach
      </div>
    @endif

    <div class="overflow-x-auto">
      <table class="w-full min-w-[760px] text-sm">
        <thead>
          <tr class="text-left {{ $labelClass }}">
            <th class="pb-2 pr-3">Fecha</th>
            <th class="pb-2 pr-3">Actividad</th>
            <th class="pb-2 pr-3">Tipo</th>
            <th class="pb-2 pr-3">Quién</th>
            <th class="pb-2 pr-3">Hito</th>
            <th class="pb-2 pr-3 text-right">Horas</th>
            <th class="pb-2"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
          @forelse ($entries as $entry)
            <tr>
              <td class="py-3 pr-3 font-mono text-xs text-zinc-400">{{ $entry->entry_date->format('d/m/Y') }}</td>
              <td class="py-3 pr-3">
                {{ $entry->activity }}
                @if ($entry->source === 'reconstruido')
                  {{-- Marca interna: no viaja al reporte del cliente. --}}
                  <span class="ml-2 rounded border border-zinc-500/30 bg-zinc-500/10 px-1.5 py-0.5 font-mono text-[10px] uppercase tracking-widest text-zinc-400">reconstruido</span>
                @endif

                {{-- Corregir fecha, horas, tipo o a qué hito pertenece. --}}
                <details class="mt-1">
                  <summary class="cursor-pointer {{ $labelClass }} hover:text-zinc-300">Editar</summary>
                  <form method="POST" action="{{ route('admin.time.update', $entry) }}" class="mt-2 grid gap-2 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <input type="date" name="entry_date" value="{{ $entry->entry_date->format('Y-m-d') }}" required class="{{ $inputClass }}" />
                    <input type="number" name="hours" value="{{ $entry->hours }}" min="0.25" step="0.25" required class="{{ $inputClass }}" />
                    <input type="text" name="activity" value="{{ $entry->activity }}" required maxlength="255" class="{{ $inputClass }} sm:col-span-2" />
                    <select name="category" class="{{ $selectClass }}">
                      @foreach (TimeEntry::CATEGORIAS as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($entry->category === $valor)>{{ $etiqueta }}</option>
                      @endforeach
                    </select>
                    <select name="consultant_id" class="{{ $selectClass }}">
                      <option value="">Sin asignar</option>
                      @foreach ($consultants as $consultant)
                        <option value="{{ $consultant->id }}" @selected($entry->consultant_id === $consultant->id)>{{ $consultant->name }}</option>
                      @endforeach
                    </select>
                    <select name="milestone_id" class="{{ $selectClass }}">
                      <option value="">Sin hito</option>
                      @foreach ($project->milestones as $milestone)
                        <option value="{{ $milestone->id }}" @selected($entry->milestone_id === $milestone->id)>{{ $milestone->label }}</option>
                      @endforeach
                    </select>
                    <button class="{{ $btnClass }}">Guardar</button>
                  </form>
                </details>
              </td>
              <td class="py-3 pr-3 font-mono text-xs text-zinc-400">{{ $entry->categoriaLegible() }}</td>
              <td class="py-3 pr-3 text-xs text-zinc-400">{{ $entry->consultant?->name ?? '—' }}</td>
              <td class="py-3 pr-3 text-xs text-zinc-400">{{ $entry->milestone?->label ?? '—' }}</td>
              <td class="py-3 pr-3 text-right font-mono">{{ number_format($entry->hours, 2) }}</td>
              <td class="py-3 text-right">
                <form method="POST" action="{{ route('admin.time.destroy', $entry) }}"
                      onsubmit="return confirm('¿Eliminar este renglón?')">
                  @csrf
                  @method('DELETE')
                  <button class="font-mono text-[10px] uppercase tracking-widest text-zinc-500 transition hover:text-red-300">Borrar</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="py-6 text-center text-sm text-zinc-500">Sin horas registradas todavía.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</section>
