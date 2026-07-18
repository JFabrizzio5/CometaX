<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('avatar_path')->nullable();

            // Los consultores pueden tener acceso de staff (incluye al
            // mega-admin, que es simplemente role=super_admin).
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('consultant'); // consultant|admin|super_admin
            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultants');
    }
};
