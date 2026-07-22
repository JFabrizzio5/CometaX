<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Consultant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión de citas desde el panel interno: agenda de próximas/pasadas,
 * alta y edición. El alta admin siempre es para un cliente (lead_id no aplica).
 */
class AppointmentAdminController extends Controller
{
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
