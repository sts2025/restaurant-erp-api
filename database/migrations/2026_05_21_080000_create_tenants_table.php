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
    Schema::create('tenants', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('business_type')->default('restaurant'); // restaurant, bakery, etc.
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->string('address')->nullable();
        $table->string('currency')->default('UGX');
        $table->string('timezone')->default('Africa/Kampala');
        $table->boolean('status')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
