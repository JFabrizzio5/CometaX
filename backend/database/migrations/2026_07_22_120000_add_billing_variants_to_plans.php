<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada plan tiene dos modalidades de cobro:
 *   - domiciliado: suscripción recurrente mensual (tarjeta guardada), precio preferente.
 *   - pago único:  cobro puntual del mes vía Stripe Checkout (mode=payment), sin guardar tarjeta.
 *
 * `price_cents` (columna previa) queda como el precio de PAGO ÚNICO (el de lista).
 * Se agrega `price_domiciliado_cents` para el precio con descuento de la suscripción.
 * Cada modalidad apunta a su propio Stripe Price.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('price_domiciliado_cents')->nullable()->after('price_cents');
            $table->string('stripe_product_id')->nullable()->after('stripe_price_id');
            $table->string('stripe_price_id_recurring')->nullable()->after('stripe_product_id');
            $table->string('stripe_price_id_onetime')->nullable()->after('stripe_price_id_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'price_domiciliado_cents',
                'stripe_product_id',
                'stripe_price_id_recurring',
                'stripe_price_id_onetime',
            ]);
        });
    }
};
