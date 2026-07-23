<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloqueos de agenda del equipo: un día completo o un rango de horas marcados
 * como ocupados/no disponibles para agendar citas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->boolean('all_day')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('consultant_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
    }
};
