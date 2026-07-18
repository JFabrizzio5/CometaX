<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('project_type'); // web|app|ecommerce|consultoria|soporte
            $table->unsignedInteger('project_type_base_price_cents');
            $table->string('urgency')->default('normal'); // normal|rapido|urgente
            $table->decimal('urgency_multiplier', 3, 2)->default(1.00);
            $table->text('description')->nullable();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->unsignedInteger('calculated_total_cents');
            $table->string('status')->default('enviada'); // enviada|revisada|convertida
            $table->foreignId('converted_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
