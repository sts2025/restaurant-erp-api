<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
/**
 * MIGRATION: Add address and phone to branches table
 *
 * address and phone are branch-specific — not business-wide.
 * They already exist as fields in BranchController but may not
 * be in the branches table depending on your original migration.
 * This ensures they exist safely.
 *
 * Run with: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'address')) {
                $table->string('address')->nullable()->after('name');
            }
            if (!Schema::hasColumn('branches', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }
        });
    }
 
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone']);
        });
    }
};