<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Update semua record dengan formula yang benar
        DB::statement('UPDATE stock_opnames SET difference = physical_stock - system_stock');
    }

    public function down()
    {
        // Rollback ke formula lama jika diperlukan
        DB::statement('UPDATE stock_opnames SET difference = system_stock - physical_stock');
    }
};