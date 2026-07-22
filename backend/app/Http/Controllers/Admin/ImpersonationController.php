<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Consultant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * "Ver como cliente": el staff entra al portal tal como lo ve un cliente,
 * para revisar/depurar lo que percibe. Guarda en sesión el consultor que
 * inició la vista para poder volver al panel interno.
 *
 * La clave de sesión IMPERSONATOR es la capacidad: mientras exista, el portal
 * muestra la barra "estás viendo como…" y /salir-vista-cliente restaura al staff.
 */
class ImpersonationController extends Controller
{
    private const KEY = 'impersonator_consultant_id';

    /** Inicia la vista como uno de los usuarios del cliente. (ruta bajo 'staff') */
    public function start(Client $client): RedirectResponse
    {
        // Entra como el admin del cliente si existe; si no, el primer usuario.
        $user = $client->users()->where('role', 'admin')->first()
            ?? $client->users()->first();

        if ($user === null) {
            return back()->withErrors([
                'impersonate' => 'Este cliente no tiene usuarios; agrega uno antes de verlo como cliente.',
            ]);
        }

        $consultantId = Auth::guard('consultant')->id();

        Auth::guard('consultant')->logout();
        Auth::guard('web')->login($user);

        // regenerate() rota el id de sesión pero conserva los datos; guardamos
        // el consultor DESPUÉS para que sobreviva la rotación.
        session()->regenerate();
        session()->put(self::KEY, $consultantId);

        return redirect()->route('dashboard');
    }

    /** Sale de la vista y vuelve a la sesión de staff. (ruta pública, guard web) */
    public function stop(): RedirectResponse
    {
        $consultantId = session()->pull(self::KEY);

        if ($consultantId === null) {
            return redirect()->route('login');
        }

        $consultant = Consultant::find($consultantId);

        Auth::guard('web')->logout();

        if ($consultant === null) {
            session()->regenerate();

            return redirect()->route('admin.login');
        }

        Auth::guard('consultant')->login($consultant);
        session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}
