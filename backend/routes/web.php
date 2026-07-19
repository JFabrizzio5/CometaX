<?php

use App\Http\Controllers\Admin\SubscriptionOverviewController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
});

// Panel interno
Route::middleware('staff')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/suscripciones', SubscriptionOverviewController::class)->name('subscriptions');
});
