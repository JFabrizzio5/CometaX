<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // Puede ser un cliente ya existente o un lead (prospecto público
            // agendando una asesoría sin cuenta todavía) — uno de los dos.
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->cascadeOnDelete();

            $table->foreignId('consultant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('meeting_type'); // junta_mensual|soporte_tecnico|consultoria_nuevo_proyecto|revision_contrato|asesoria_publica
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->string('status')->default('solicitada'); // solicitada|confirmada|cancelada|completada
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
