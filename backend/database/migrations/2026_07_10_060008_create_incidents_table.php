<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_code')->unique(); // ej. A-114, se genera en un observer del modelo
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('media'); // baja|media|urgente
            $table->string('status')->default('nuevo'); // nuevo|revision|progreso|resuelto
            $table->foreignId('assignee_consultant_id')->nullable()->constrained('consultants')->nullOnDelete();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
