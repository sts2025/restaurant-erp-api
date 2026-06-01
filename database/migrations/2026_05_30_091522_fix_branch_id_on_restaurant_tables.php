<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {

            if (!Schema::hasColumn('restaurant_tables', 'branch_id')) {

                $table->unsignedBigInteger('branch_id')
                    ->nullable()
                    ->after('id');

            }

        });
    }

    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {

            if (Schema::hasColumn('restaurant_tables', 'branch_id')) {

                $table->dropColumn('branch_id');

            }

        });
    }
};