<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Incident;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectActivity;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Resumen del negocio para el staff: ganancias (MRR, cobrado), cartera de
 * clientes, carga operativa (proyectos/incidencias) y agenda próxima.
 */
class AdminDashboardController extends Controller
{
    private const PAYING_STATUSES = ['active', 'trialing'];

    public function __invoke(): View
    {
        $clients = Client::with(['plan', 'subscriptions'])->get();

        $payingClients = $clients->filter(function (Client $client): bool {
            $current = $client->subscriptions
                ->sortByDesc('created_at')
                ->first(fn ($sub) => $sub->ends_at === null || $sub->ends_at->isFuture());

            return in_array($current?->stripe_status, self::PAYING_STATUSES, true);
        });

        // MRR con el precio domiciliado (es el que cobra la suscripción).
        $mrrCents = $payingClients->sum(
            fn (Client $client) => $client->plan?->price_domiciliado_cents
                ?? $client->plan?->price_cents
                ?? 0,
        );

        $paidInvoices = Invoice::where('status', 'pagado');

        $stats = [
            'mrr_cents' => $mrrCents,
            'clientes' => $clients->count(),
            'pagando' => $payingClients->count(),
            'cobrado_mes_cents' => (clone $paidInvoices)
                ->whereBetween('invoice_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->sum('amount_cents'),
            'cobrado_total_cents' => $paidInvoices->sum('amount_cents'),
            'proyectos_activos' => Project::where('status', '!=', 'finalizado')->count(),
            'incidencias_abiertas' => Incident::where('status', '!=', 'resuelto')->count(),
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'proximasCitas' => Appointment::with(['client', 'lead'])
                ->whereDate('appointment_date', '>=', Carbon::today())
                ->where('status', '!=', 'cancelada')
                ->orderBy('appointment_date')
                ->orderBy('start_time')
                ->limit(6)
                ->get(),
            'ultimosAvances' => ProjectActivity::with('project.client')
                ->latest('occurred_at')
                ->limit(6)
                ->get(),
            'ultimosCobros' => Invoice::with('client')
                ->latest('invoice_date')
                ->limit(6)
                ->get(),
        ]);
    }
}
