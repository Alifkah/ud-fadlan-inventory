<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_credit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_credit_id')->constrained()->onDelete('cascade');
            $table->decimal('payment_amount', 15, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'transfer', 'check']);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_credit_payments');
    }
};