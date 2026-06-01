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
    Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
        $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // The cashier
        $table->string('receipt_number')->unique();
        $table->decimal('total', 10, 2);
        $table->decimal('paid', 10, 2);
        $table->decimal('change', 10, 2);
        $table->string('payment_method')->default('Cash'); // Cash, Card, Mobile
        $table->boolean('is_void') ->default(false);
        $table->text('void_reason')->nullable();
        $table->foreignId('voided_by')->nullable();
         $table->foreignId('branch_id') ->nullable() ->constrained();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
