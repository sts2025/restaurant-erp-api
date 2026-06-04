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
    Schema::create('stock_movements', function (Blueprint $table) {

        $table->id();

        $table->foreignId('tenant_id');

        $table->foreignId('branch_id')
              ->nullable();

        $table->foreignId('product_id');

        $table->foreignId('user_id');

        $table->enum('type', [
            'in',
            'out',
            'adjust'
        ]);

        $table->integer('quantity');

        $table->text('reason')
              ->nullable();

        $table->timestamps();
    });
}
};
