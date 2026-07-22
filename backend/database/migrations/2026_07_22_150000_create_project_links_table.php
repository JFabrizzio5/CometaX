<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaces de un proyecto: repos de GitHub, staging, producción, docs, etc.
 * kind es solo para el ícono/etiqueta; la url es lo que importa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('kind')->default('otro'); // github|repo|staging|produccion|doc|otro
            $table->string('label');
            $table->string('url', 1000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_links');
    }
};
