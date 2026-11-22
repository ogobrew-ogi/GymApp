<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A weekly slot an admin reserves (e.g. Mon + Wed, 10:00–11:00).
        // Real trainings are generated from it for the booking window,
        // so members join them like any other training.
        Schema::create('recurring_trainings', function (Blueprint $table): void {
            $table->id();

            // ISO weekdays, 1 = Monday … 7 = Sunday.
            $table->json('weekdays');

            // Local (Europe/Sofia) wall-clock times, "H:i".
            $table->string('start_time', 5);
            $table->string('end_time', 5);

            $table->unsignedSmallInteger('max_participants');
            $table->string('note', 255)->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // Last local date trainings were generated for. Generation
            // only moves forward from here, so a single cancelled
            // occurrence is never recreated.
            $table->date('generated_until')->nullable();

            $table->timestamps();
        });

        Schema::table('trainings', function (Blueprint $table): void {
            $table->foreignId('recurring_training_id')
                ->nullable()
                ->after('owner_id')
                ->constrained('recurring_trainings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('recurring_training_id');
        });

        Schema::dropIfExists('recurring_trainings');
    }
};
