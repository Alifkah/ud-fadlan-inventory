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
        if (!Schema::hasColumn('settings', 'group')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('group')->default('general')->after('type');
                $table->index('group');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('settings', 'group')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropIndex(['group']);
                $table->dropColumn('group');
            });
        }
    }
};