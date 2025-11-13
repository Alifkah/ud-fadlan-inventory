<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->date('sale_date');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->enum('payment_method', ['cash', 'credit', 'transfer']);
            $table->enum('status', ['pending', 'completed', 'canceled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['sale_date', 'status']);
            $table->index('payment_method');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales');
    }
};

return new class extends Migration
{
    public function up()
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();

            $table->index(['sale_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_items');
    }
};

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'current_stock']);
            $table->index('code');
            $table->index('name');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index(['is_active', 'name']);
            $table->index('code');
            $table->index('phone');
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->index(['product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'current_stock']);
            $table->dropIndex(['code']);
            $table->dropIndex(['name']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'name']);
            $table->dropIndex(['code']);
            $table->dropIndex(['phone']);
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'created_at']);
            $table->dropIndex(['reference_type', 'reference_id']);
        });
    }
};

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->nullable()->after('total');
            $table->decimal('change_amount', 15, 2)->nullable()->after('amount_paid');
            
            $table->index(['created_at', 'status']);
            $table->index(['user_id', 'sale_date']);
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'change_amount']);
            $table->dropIndex(['created_at', 'status']);
            $table->dropIndex(['user_id', 'sale_date']);
        });
    }
};

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transactions', 'reference_type')) {
                $table->string('reference_type')->nullable();
            }
            if (!Schema::hasColumn('stock_transactions', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transactions', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
            if (Schema::hasColumn('stock_transactions', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
        });
    }
};