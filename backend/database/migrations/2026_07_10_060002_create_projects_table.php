<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_consultant_id')->nullable()->constrained('consultants')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('activo'); // activo|en_revision|finalizado
            $table->date('start_date')->nullable();
            $table->date('estimated_delivery')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->decimal('hours_budgeted', 8, 2)->default(0);
            $table->decimal('hours_used', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
