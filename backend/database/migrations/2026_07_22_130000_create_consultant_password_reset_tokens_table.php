<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tokens de reset/definición de contraseña para consultants (staff), separados
 * de los de clientes (users). Tabla propia para que un mismo correo en ambos
 * lados no colisione tokens.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultant_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_password_reset_tokens');
    }
};
