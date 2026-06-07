<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRATION: Add branch_id to expenses table
 *
 * Run with:  php artisan migrate
 *
 * Expenses were previously not scoped to a branch.
 * This adds branch_id so each branch only sees its own expenses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')
                      ->nullable()
                      ->after('tenant_id')
                      ->comment('Branch this expense belongs to');

                $table->foreign('branch_id')
                      ->references('id')
                      ->on('branches')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
