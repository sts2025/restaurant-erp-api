<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() 
{
    Schema::table('sales', function (Blueprint $table) {
        $table->unsignedBigInteger('shift_id')->nullable()->after('user_id');
        $table->unsignedBigInteger('table_id')->nullable()->after('payment_method');
        // If these columns exist but are just named differently, please verify your table structure.
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            //
        });
    }
};
