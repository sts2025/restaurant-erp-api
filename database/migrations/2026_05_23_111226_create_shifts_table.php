<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RUN MIGRATIONS
     */
    public function up(): void
    {
        Schema::dropIfExists('shifts');

        Schema::create('shifts', function (Blueprint $table) {

            $table->id();

            /**
             * TENANT
             */
            $table->foreignId('tenant_id')
                ->default(1);

            /**
             * USER / CASHIER
             */
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * BUSINESS DATE
             */
            $table->date('business_date');

            /**
             * SHIFT TIMES
             */
            $table->timestamp('start_time');

            $table->timestamp('end_time')
                ->nullable();

            /**
             * CASH MANAGEMENT
             */
            $table->decimal('starting_cash', 12, 2)
                ->default(0);

            $table->decimal('closing_cash', 12, 2)
                ->nullable();

            $table->decimal('expected_cash', 12, 2)
                ->nullable();

            $table->decimal('cash_difference', 12, 2)
                ->nullable();

            /**
             * SHIFT STATUS
             */
            $table->enum('status', [
                'open',
                'closed'
            ])->default('open');

            /**
             * TIMESTAMPS
             */
            $table->timestamps();

        });
    }

    /**
     * REVERSE MIGRATIONS
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};