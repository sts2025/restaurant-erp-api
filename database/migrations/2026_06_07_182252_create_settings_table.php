<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
/**
 * MIGRATION: Create settings table
 *
 * Stores key-value business settings per tenant.
 * Run with: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->timestamps();
 
            // Each key is unique per tenant
            $table->unique(['tenant_id', 'key']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
 