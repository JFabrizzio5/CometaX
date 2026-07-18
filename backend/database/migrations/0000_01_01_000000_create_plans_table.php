<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('included_hours');
            $table->unsignedInteger('hourly_overage_rate_cents');
            $table->string('billing_cycle')->default('mensual');
            $table->string('stripe_price_id')->nullable();
            $table->string('status')->default('activo');

            // Control de capacidad: cuántos clientes puede atender la
            // consultora simultáneamente en este plan. null = sin límite.
            $table->unsignedInteger('max_clients')->nullable();

            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
