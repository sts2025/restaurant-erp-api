<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRATION: Add is_unlimited to products table
 *
 * Products flagged as unlimited (e.g. tea, coffee, water)
 * will never have stock decremented on sale and will never
 * trigger low-stock warnings.
 *
 * Run with: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_unlimited')) {
                $table->boolean('is_unlimited')
                      ->default(false)
                      ->after('stock_quantity')
                      ->comment('If true, stock is never decremented on sale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_unlimited')) {
                $table->dropColumn('is_unlimited');
            }
        });
    }
};
