<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('concept');
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('mxn');
            $table->string('payment_method_masked')->nullable();
            $table->string('status')->default('pendiente'); // pendiente|pagado|fallido
            $table->timestamp('paid_at')->nullable();
            $table->date('invoice_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
