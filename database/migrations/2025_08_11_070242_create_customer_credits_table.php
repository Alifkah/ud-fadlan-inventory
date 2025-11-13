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
        if (Schema::hasTable('customer_credits')) {
            Schema::table('customer_credits', function (Blueprint $table) {
                $table->enum('status', ['active', 'paid', 'overdue'])
                      ->default('active')
                      ->change();
            });
        } else {
            Schema::create('customer_credits', function (Blueprint $table) {
                $table->id();
                $table->string('credit_number', 50)->unique();
                $table->foreignId('customer_id')->constrained()->onDelete('cascade');
                $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('total_credit', 15, 2);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->decimal('remaining_amount', 15, 2);
                $table->date('due_date');
                $table->enum('status', ['active', 'paid', 'overdue'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('customer_id');
                $table->index('status');
                $table->index('due_date');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_credits');
    }
};
