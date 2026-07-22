<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Avisos del equipo hacia clientes desde el panel interno.
 * client_id null = aviso general (lo ven todos los clientes).
 */
class AnnouncementAdminController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::with(['client', 'author'])
            ->latest('published_at')
            ->get();

        return view('admin.announcements.index', ['announcements' => $announcements]);
    }

    public function create(): View
    {
        return view('admin.announcements.form', [
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'client_id' => ['nullable', 'exists:clients,id'],
        ]);

        Announcement::create([
            ...$data,
            'consultant_id' => auth('consultant')->id(),
            'published_at' => now(),
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('status', 'Aviso publicado.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('status', 'Aviso eliminado.');
    }
}
