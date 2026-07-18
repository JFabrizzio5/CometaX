<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_quote_scope_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_scope_item_id')->constrained()->cascadeOnDelete();
            // El catálogo puede cambiar de precio después; la cotización ya
            // enviada debe conservar el precio que tenía en ese momento.
            $table->unsignedInteger('price_cents_snapshot');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_quote_scope_item');
    }
};
