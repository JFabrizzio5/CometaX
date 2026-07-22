<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión de consultores (equipo interno) — solo super_admin.
 *
 * Dar de alta un consultor crea la cuenta y genera un enlace para que defina
 * su contraseña. Se intenta enviar por correo; como el SMTP puede no estar
 * listo, el enlace también se muestra en pantalla para pasarlo a mano.
 */
class ConsultantAdminController extends Controller
{
    private const ROLES = ['consultant', 'admin', 'super_admin'];

    public function index(): View
    {
        $this->guard();

        return view('admin.consultants.index', [
            'consultants' => Consultant::withCount('projects')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->guard();

        return view('admin.consultants.form', ['consultant' => new Consultant, 'roles' => self::ROLES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guard();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:consultants,email'],
            'title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $consultant = Consultant::create($data);

        return redirect()->route('admin.consultants.show', $consultant)
            ->with($this->inviteFlash($consultant, "Consultor «{$consultant->name}» creado."));
    }

    public function show(Consultant $consultant): View
    {
        $this->guard();

        $consultant->load(['projects.client', 'assignedIncidents']);

        return view('admin.consultants.show', ['consultant' => $consultant]);
    }

    public function edit(Consultant $consultant): View
    {
        $this->guard();

        return view('admin.consultants.form', ['consultant' => $consultant, 'roles' => self::ROLES]);
    }

    public function update(Request $request, Consultant $consultant): RedirectResponse
    {
        $this->guard();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('consultants', 'email')->ignore($consultant->id)],
            'title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        // No dejar el sistema sin ningún super_admin (p.ej. degradándote a ti mismo).
        if ($consultant->isSuperAdmin() && $data['role'] !== 'super_admin'
            && Consultant::where('role', 'super_admin')->count() <= 1) {
            return back()->withErrors(['role' => 'No puedes quitar el último super_admin.'])->withInput();
        }

        $consultant->update($data);

        return redirect()->route('admin.consultants.show', $consultant)
            ->with('status', 'Consultor actualizado.');
    }

    /** Reenvía / regenera el enlace para definir contraseña. */
    public function invite(Consultant $consultant): RedirectResponse
    {
        $this->guard();

        return redirect()->route('admin.consultants.show', $consultant)
            ->with($this->inviteFlash($consultant, 'Enlace de acceso regenerado.'));
    }

    /**
     * Genera el enlace de definir-contraseña, intenta enviarlo por correo y
     * devuelve el flash (status + invite_link para mostrarlo en pantalla).
     *
     * @return array<string, string>
     */
    private function inviteFlash(Consultant $consultant, string $status): array
    {
        $token = Password::broker('consultants')->createToken($consultant);
        $url = route('admin.password.reset', ['token' => $token, 'email' => $consultant->email]);

        // Intento de correo; si el SMTP no está listo no rompe el alta.
        try {
            $consultant->sendPasswordResetNotification($token);
        } catch (\Throwable) {
            // El enlace en pantalla es el respaldo.
        }

        return ['status' => $status, 'invite_link' => $url];
    }

    private function guard(): void
    {
        abort_unless(auth('consultant')->user()?->isSuperAdmin(), 403, 'Solo un super_admin gestiona consultores.');
    }
}
