<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (
            Blueprint $table
        ) {

            $table->id();

            $table->foreignId('tenant_id')
                ->nullable();

            $table->foreignId('user_id')
                ->nullable();

            $table->string('title');

            $table->decimal(
                'amount',
                12,
                2
            );

            $table->string('category')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'expenses'
        );
    }
};