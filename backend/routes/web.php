<?php

use App\Http\Controllers\Admin\SubscriptionOverviewController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

Route::get('/', function () {
    if (Auth::guard('consultant')->check()) {
        return redirect()->route('admin.subscriptions');
    }

    return redirect()->route(Auth::guard('web')->check() ? 'dashboard' : 'login');
});

Route::get('/login', fn () => view('auth.login'))->name('login');

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', [GoogleController::class, 'logout'])->name('logout');

// Portal de cliente
Route::middleware('auth:web')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    // Facturación: se apaga sola con PAYMENTS_MAINTENANCE (payments.available).
    Route::middleware('payments.available')->group(function () {
        Route::get('/planes', [BillingController::class, 'index'])->name('billing.planes');
        Route::post('/planes/{plan}/domiciliar', [BillingController::class, 'domiciliar'])->name('billing.domiciliar');
        Route::post('/planes/{plan}/unico', [BillingController::class, 'unico'])->name('billing.unico');
        Route::get('/pago/exito', [BillingController::class, 'exito'])->name('billing.exito');
        Route::get('/pago/cancelado', [BillingController::class, 'cancelado'])->name('billing.cancelado');
    });
});

// Webhook de Stripe: sin auth ni CSRF (ya exento en bootstrap), firmado.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->middleware(VerifyWebhookSignature::class)
    ->name('cashier.webhook');

// Panel interno
Route::middleware('staff')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/suscripciones', SubscriptionOverviewController::class)->name('subscriptions');
});
