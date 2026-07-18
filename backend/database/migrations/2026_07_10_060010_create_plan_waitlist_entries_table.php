<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cuando un plan llega a max_clients, el prospecto del landing elige
        // una de tres rutas (todas quedan registradas aquí): ofertar cuánto
        // está dispuesto a pagar, agendar una asesoría, o solo quedar en
        // cola simple para que lo contacten.
        Schema::create('plan_waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('offer_amount_cents')->nullable();
            $table->boolean('wants_appointment')->default(false);
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pendiente'); // pendiente|contactado|convertido|rechazado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_waitlist_entries');
    }
};
