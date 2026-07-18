<?php

namespace App\Providers;

use App\Models\Client;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Client (empresa/tenant) es el modelo facturable, no User.
        Cashier::useCustomerModel(Client::class);
    }
}
