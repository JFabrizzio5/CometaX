<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('document'); // document|contract
            $table->string('title');
            $table->string('filename');
            $table->string('file_path');
            $table->string('version')->nullable();
            $table->string('status')->nullable(); // firmado|vigente|por_renovar|vencido...
            $table->date('signed_date')->nullable();
            $table->string('term_length')->nullable();
            $table->date('renewal_date')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
