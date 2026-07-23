<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Client;
use App\Models\Consultant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión de citas desde el panel interno: agenda de próximas/pasadas,
 * alta y edición. El alta admin siempre es para un cliente (lead_id no aplica).
 */
class AppointmentAdminController extends Controller
{
    /** Calendario de mes: citas de todos los clientes + bloqueos, con detalle del día. */
    public function calendar(Request $request): View
    {
        $ym = (string) $request->query('ym', '');
        $monthDate = preg_match('/^\d{4}-\d{2}$/', $ym)
            ? Carbon::createFromFormat('Y-m-d', $ym.'-01')->startOfMonth()
            : Carbon::today()->startOfMonth();

        $dia = (string) $request->query('dia', '');
        $selected = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia) ? Carbon::parse($dia) : Carbon::today();

        $citasMes = Appointment::with('client', 'lead')
            ->whereYear('appointment_date', $monthDate->year)->whereMonth('appointment_date', $monthDate->month)
            ->orderBy('start_time')->get()->groupBy(fn ($a) => (int) $a->appointment_date->format('j'));

        $bloqueosMes = BlockedSlot::whereYear('date', $monthDate->year)->whereMonth('date', $monthDate->month)
            ->orderBy('start_time')->get()->groupBy(fn ($b) => (int) $b->date->format('j'));

        $today = Carbon::today();
        $cells = array_fill(0, $monthDate->dayOfWeekIso - 1, null);
        for ($d = 1; $d <= $monthDate->daysInMonth; $d++) {
            $date = $monthDate->copy()->day($d);
            $cells[] = [
                'day' => $d, 'date' => $date->toDateString(),
                'today' => $date->isSameDay($today), 'past' => $date->lt($today),
                'selected' => $date->isSameDay($selected),
                'citas' => $citasMes->get($d, collect()),
                'bloqueos' => $bloqueosMes->get($d, collect()),
            ];
        }
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return view('admin.appointments.calendar', [
            'monthDate' => $monthDate,
            'weeks' => array_chunk($cells, 7),
            'prevYm' => $monthDate->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextYm' => $monthDate->copy()->addMonthNoOverflow()->format('Y-m'),
            'selected' => $selected,
            'citasDia' => Appointment::with('client', 'lead', 'consultant')
                ->whereDate('appointment_date', $selected)->orderBy('start_time')->get(),
            'bloqueosDia' => BlockedSlot::whereDate('date', $selected)->orderBy('start_time')->get(),
        ]);
    }

    public function confirm(Appointment $appointment): RedirectResponse
    {
        $appointment->update(['status' => 'confirmada']);

        return back()->with('status', 'Cita confirmada.');
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $appointment->update(['status' => 'cancelada']);

        return back()->with('status', 'Cita cancelada.');
    }

    /** Bloquea un día completo o un rango de horas (marca como ocupado). */
    public function block(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'all_day' => ['nullable', 'boolean'],
            'start_time' => ['nullable', 'required_if:all_day,0', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_if:all_day,0', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:150'],
        ]);

        $allDay = ! $request->has('start_time') || $request->boolean('all_day');

        BlockedSlot::create([
            'date' => $data['date'],
            'all_day' => $allDay,
            'start_time' => $allDay ? null : $data['start_time'],
            'end_time' => $allDay ? null : $data['end_time'],
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', $allDay ? 'Día bloqueado.' : 'Horario bloqueado.');
    }

    public function unblock(BlockedSlot $block): RedirectResponse
    {
        $block->delete();

        return back()->with('status', 'Bloqueo eliminado.');
    }

    public function index(Request $request): View
    {
        $ver = $request->query('ver') === 'pasadas' ? 'pasadas' : 'proximas';

        $appointments = Appointment::with(['client', 'lead', 'consultant'])
            ->when(
                $ver === 'pasadas',
                fn ($query) => $query->whereDate('appointment_date', '<', today())
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('start_time'),
                fn ($query) => $query->whereDate('appointment_date', '>=', today())
                    ->orderBy('appointment_date')
                    ->orderBy('start_time'),
            )
            ->get();

        return view('admin.appointments.index', ['appointments' => $appointments, 'ver' => $ver]);
    }

    public function create(Request $request): View
    {
        // Preselecciona el cliente cuando se llega desde su ficha (?client=ID).
        $appointment = new Appointment;
        $appointment->client_id = $request->integer('client') ?: null;

        return view('admin.appointments.form', [
            'appointment' => $appointment,
            'clients' => Client::orderBy('name')->get(),
            'consultants' => Consultant::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Appointment::create($this->validated($request));

        return redirect()->route('admin.appointments.index')
            ->with('status', 'Cita creada.');
    }

    public function edit(Appointment $appointment): View
    {
        return view('admin.appointments.form', [
            'appointment' => $appointment,
            'clients' => Client::orderBy('name')->get(),
            'consultants' => Consultant::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $this->validated($request, $appointment);

        // Invariante de la tabla: client_id O lead_id, nunca ambos. En una cita
        // de prospecto, elegir cliente la convierte; dejarlo vacío conserva el lead.
        if ($appointment->lead_id !== null) {
            if (! empty($data['client_id'])) {
                $data['lead_id'] = null;
            } else {
                unset($data['client_id']);
            }
        }

        $appointment->update($data);

        return redirect()->route('admin.appointments.index')
            ->with('status', 'Cita actualizada.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Appointment $appointment = null): array
    {
        // Citas de prospecto (lead) no exigen cliente: el lead es el destinatario.
        $clientRule = $appointment?->lead_id !== null ? 'nullable' : 'required';

        return $request->validate([
            'client_id' => [$clientRule, 'exists:clients,id'],
            'consultant_id' => ['nullable', 'exists:consultants,id'],
            'meeting_type' => ['required', Rule::in([
                'junta_mensual', 'soporte_tecnico', 'consultoria_nuevo_proyecto',
                'revision_contrato', 'asesoria_publica',
            ])],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['solicitada', 'confirmada', 'cancelada', 'completada'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
