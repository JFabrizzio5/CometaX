<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Gestión de clientes desde el panel interno: alta, edición, detalle
 * (usuarios, proyectos, facturas) y alta de usuarios del cliente.
 */
class ClientAdminController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $clients = Client::with('plan')
            ->withCount(['users', 'projects'])
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('contact_email', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->get();

        return view('admin.clients.index', ['clients' => $clients, 'q' => $q]);
    }

    public function create(): View
    {
        return view('admin.clients.form', [
            'client' => new Client,
            'plans' => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $client = Client::create($data);

        return redirect()->route('admin.clients.show', $client)
            ->with('status', "Cliente «{$client->name}» creado.");
    }

    public function show(Client $client): View
    {
        $client->load([
            'plan',
            'users',
            'projects' => fn ($query) => $query->latest(),
            'invoices' => fn ($query) => $query->latest('invoice_date')->limit(12),
        ]);

        return view('admin.clients.show', ['client' => $client]);
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.form', [
            'client' => $client,
            'plans' => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validated($request));

        return redirect()->route('admin.clients.show', $client)
            ->with('status', 'Cliente actualizado.');
    }

    /**
     * Alta de un usuario del cliente. Queda sin contraseña: la define cuando
     * el login de clientes esté activo (o entra con Google si ya está ligado).
     */
    public function storeUser(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,member'],
        ]);

        // Usuario dado de alta por el equipo: correo de confianza, ya verificado.
        $client->users()->create([...$data, 'email_verified_at' => now()]);

        return redirect()->route('admin.clients.show', $client)
            ->with('status', "Usuario {$data['email']} agregado.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'plan_id' => ['nullable', 'exists:plans,id'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'cliente';
        $slug = $base;
        $suffix = 2;

        while (Client::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
