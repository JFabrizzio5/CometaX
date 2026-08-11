<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AnnouncementAdminController;
use App\Http\Controllers\Admin\AppointmentAdminController;
use App\Http\Controllers\Admin\ClientAdminController;
use App\Http\Controllers\Admin\ConsultantAdminController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\IncidentAdminController;
use App\Http\Controllers\Admin\ProjectAdminController;
use App\Http\Controllers\Admin\SubscriptionOverviewController;
use App\Http\Controllers\Admin\TimeEntryAdminController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\HomeRedirectController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

// Controller invokable, no Closure: route:cache no serializa Closures y dejaba
// la raíz respondiendo solo HEAD (405 en GET). Ver HomeRedirectController.
Route::get('/', HomeRedirectController::class);

Route::view('/login', 'auth.login')->name('login');
Route::post('/login', [ClientAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');

// Auto-registro de clientes (correo + contraseña).
Route::get('/registro', [ClientAuthController::class, 'showRegister'])->name('register');
Route::post('/registro', [ClientAuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');

// Acceso demo (gateado por features.demo_login dentro del controller).
Route::get('/demo', [DemoController::class, 'entrar'])->name('demo.entrar');

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', [GoogleController::class, 'logout'])->name('logout');

// Verificación de correo (usuario autenticado, aún sin verificar).
Route::middleware('auth:web')->group(function () {
    Route::get('/email/verificar', [ClientAuthController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verificar/{id}/{hash}', [ClientAuthController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/reenviar', [ClientAuthController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
});

// Portal de cliente — requiere correo verificado.
Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
    Route::get('/mis-proyectos', [ClientDashboardController::class, 'projects'])->name('client.projects');
    Route::get('/mis-proyectos/{project}', [ClientDashboardController::class, 'projectShow'])->name('client.projects.show');
    Route::get('/mis-incidencias', [ClientDashboardController::class, 'incidents'])->name('client.incidents');
    Route::post('/mis-incidencias', [ClientDashboardController::class, 'reportIncident'])->name('client.incidents.store');
    Route::post('/mis-incidencias/{incident}/evidencia', [ClientDashboardController::class, 'addIncidentEvidence'])->name('client.incidents.evidence');
    Route::get('/mi-facturacion', [ClientDashboardController::class, 'invoices'])->name('client.invoices');
    Route::get('/mis-contratos', [ClientDashboardController::class, 'contracts'])->name('client.contracts');
    Route::get('/mi-calendario', [ClientDashboardController::class, 'calendar'])->name('client.calendar');
    Route::post('/citas/solicitar', [ClientDashboardController::class, 'requestMeeting'])->name('client.appointments.request');
    Route::get('/soporte', [ClientDashboardController::class, 'support'])->name('client.support');
    Route::post('/soporte', [ClientDashboardController::class, 'submitSupport'])->name('client.support.store');

    // Activar plan gratuito no depende de Stripe.
    Route::post('/planes/{plan}/gratis', [BillingController::class, 'activarGratis'])->name('billing.gratis');

    // Facturación: se apaga sola con PAYMENTS_MAINTENANCE (payments.available).
    Route::middleware('payments.available')->group(function () {
        Route::get('/planes', [BillingController::class, 'index'])->name('billing.planes');
        Route::post('/planes/{plan}/domiciliar', [BillingController::class, 'domiciliar'])->name('billing.domiciliar');
        Route::post('/planes/{plan}/unico', [BillingController::class, 'unico'])->name('billing.unico');
        Route::get('/pago/exito', [BillingController::class, 'exito'])->name('billing.exito');
        Route::get('/pago/cancelado', [BillingController::class, 'cancelado'])->name('billing.cancelado');
    });
});

// Salir de la vista "ver como cliente" y volver a la sesión de staff.
Route::post('/salir-vista-cliente', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

// Webhook de Stripe: sin auth ni CSRF (ya exento en bootstrap), firmado.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->middleware(VerifyWebhookSignature::class)
    ->name('cashier.webhook');

// Auth de staff (sin sesión): login email+password y definir/reset de contraseña.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');

    Route::get('/password/olvide', [AdminAuthController::class, 'showForgot'])->name('password.request');
    Route::post('/password/email', [AdminAuthController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/password/reset/{token}', [AdminAuthController::class, 'showReset'])->name('password.reset');
    Route::post('/password/reset', [AdminAuthController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
});

// Panel interno (requiere staff).
Route::middleware('staff')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/suscripciones', SubscriptionOverviewController::class)->name('subscriptions');

    // Clientes
    Route::get('/clientes', [ClientAdminController::class, 'index'])->name('clients.index');
    Route::get('/clientes/nuevo', [ClientAdminController::class, 'create'])->name('clients.create');
    Route::post('/clientes', [ClientAdminController::class, 'store'])->name('clients.store');
    Route::get('/clientes/{client}', [ClientAdminController::class, 'show'])->name('clients.show');
    Route::get('/clientes/{client}/editar', [ClientAdminController::class, 'edit'])->name('clients.edit');
    Route::put('/clientes/{client}', [ClientAdminController::class, 'update'])->name('clients.update');
    Route::post('/clientes/{client}/usuarios', [ClientAdminController::class, 'storeUser'])->name('clients.users.store');
    Route::post('/clientes/{client}/ver-como', [ImpersonationController::class, 'start'])->name('clients.impersonate');

    // Consultores (equipo interno). Solo super_admin — se valida en el controller.
    Route::get('/consultores', [ConsultantAdminController::class, 'index'])->name('consultants.index');
    Route::get('/consultores/nuevo', [ConsultantAdminController::class, 'create'])->name('consultants.create');
    Route::post('/consultores', [ConsultantAdminController::class, 'store'])->name('consultants.store');
    Route::get('/consultores/{consultant}', [ConsultantAdminController::class, 'show'])->name('consultants.show');
    Route::get('/consultores/{consultant}/editar', [ConsultantAdminController::class, 'edit'])->name('consultants.edit');
    Route::put('/consultores/{consultant}', [ConsultantAdminController::class, 'update'])->name('consultants.update');
    Route::post('/consultores/{consultant}/invitar', [ConsultantAdminController::class, 'invite'])->name('consultants.invite');

    // Proyectos
    Route::get('/proyectos', [ProjectAdminController::class, 'index'])->name('projects.index');
    Route::get('/proyectos/nuevo', [ProjectAdminController::class, 'create'])->name('projects.create');
    Route::post('/proyectos', [ProjectAdminController::class, 'store'])->name('projects.store');
    Route::get('/proyectos/{project}', [ProjectAdminController::class, 'show'])->name('projects.show');
    Route::put('/proyectos/{project}', [ProjectAdminController::class, 'update'])->name('projects.update');
    Route::post('/proyectos/{project}/avances', [ProjectAdminController::class, 'storeActivity'])->name('projects.activities.store');
    Route::post('/proyectos/{project}/hitos', [ProjectAdminController::class, 'storeMilestone'])->name('projects.milestones.store');
    Route::put('/hitos/{milestone}', [ProjectAdminController::class, 'updateMilestone'])->name('milestones.update');
    // Asignar consultores y enlaces (GitHub, staging, etc.) a un proyecto.
    Route::post('/proyectos/{project}/consultores', [ProjectAdminController::class, 'attachConsultant'])->name('projects.consultants.attach');
    Route::delete('/proyectos/{project}/consultores/{consultant}', [ProjectAdminController::class, 'detachConsultant'])->name('projects.consultants.detach');
    Route::post('/proyectos/{project}/enlaces', [ProjectAdminController::class, 'storeLink'])->name('projects.links.store');
    Route::delete('/enlaces/{link}', [ProjectAdminController::class, 'destroyLink'])->name('projects.links.destroy');

    // Horas: captura renglón por renglón y reconstrucción por lote.
    Route::post('/proyectos/{project}/horas', [TimeEntryAdminController::class, 'store'])->name('time.store');
    Route::post('/proyectos/{project}/horas/proponer', [TimeEntryAdminController::class, 'proponer'])->name('time.propose');
    Route::post('/proyectos/{project}/horas/confirmar', [TimeEntryAdminController::class, 'confirmar'])->name('time.confirm');
    Route::delete('/proyectos/{project}/horas/lote/{batch}', [TimeEntryAdminController::class, 'deshacerLote'])->name('time.batch.destroy');
    Route::put('/horas/{entry}', [TimeEntryAdminController::class, 'update'])->name('time.update');
    Route::delete('/horas/{entry}', [TimeEntryAdminController::class, 'destroy'])->name('time.destroy');

    // Incidencias
    Route::get('/incidencias', [IncidentAdminController::class, 'index'])->name('incidents.index');
    Route::get('/incidencias/nueva', [IncidentAdminController::class, 'create'])->name('incidents.create');
    Route::post('/incidencias', [IncidentAdminController::class, 'store'])->name('incidents.store');
    Route::get('/incidencias/{incident}/editar', [IncidentAdminController::class, 'edit'])->name('incidents.edit');
    Route::put('/incidencias/{incident}', [IncidentAdminController::class, 'update'])->name('incidents.update');
    Route::post('/incidencias/{incident}/mover', [IncidentAdminController::class, 'move'])->name('incidents.move');
    Route::post('/incidencias/{incident}/adjuntos', [IncidentAdminController::class, 'storeAttachment'])->name('incidents.attachments.store');
    Route::delete('/adjuntos/{attachment}', [IncidentAdminController::class, 'destroyAttachment'])->name('incidents.attachments.destroy');

    // Citas
    Route::get('/citas', [AppointmentAdminController::class, 'index'])->name('appointments.index');
    Route::get('/calendario', [AppointmentAdminController::class, 'calendar'])->name('appointments.calendar');
    Route::post('/citas/{appointment}/confirmar', [AppointmentAdminController::class, 'confirm'])->name('appointments.confirm');
    Route::post('/citas/{appointment}/cancelar', [AppointmentAdminController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/bloqueos', [AppointmentAdminController::class, 'block'])->name('blocks.store');
    Route::delete('/bloqueos/{block}', [AppointmentAdminController::class, 'unblock'])->name('blocks.destroy');
    Route::get('/citas/nueva', [AppointmentAdminController::class, 'create'])->name('appointments.create');
    Route::post('/citas', [AppointmentAdminController::class, 'store'])->name('appointments.store');
    Route::get('/citas/{appointment}/editar', [AppointmentAdminController::class, 'edit'])->name('appointments.edit');
    Route::put('/citas/{appointment}', [AppointmentAdminController::class, 'update'])->name('appointments.update');

    // Avisos
    Route::get('/avisos', [AnnouncementAdminController::class, 'index'])->name('announcements.index');
    Route::get('/avisos/nuevo', [AnnouncementAdminController::class, 'create'])->name('announcements.create');
    Route::post('/avisos', [AnnouncementAdminController::class, 'store'])->name('announcements.store');
    Route::delete('/avisos/{announcement}', [AnnouncementAdminController::class, 'destroy'])->name('announcements.destroy');
});
