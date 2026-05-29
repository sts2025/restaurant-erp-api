<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId(
                'product_id'
            );

            $table->foreignId(
                'user_id'
            )->nullable();

            $table->integer(
                'quantity'
            );

            $table->decimal(
                'cost',
                12,
                2
            )->default(0);

            $table->string(
                'supplier'
            )->nullable();

            $table->text(
                'notes'
            )->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'purchases'
        );
    }
};