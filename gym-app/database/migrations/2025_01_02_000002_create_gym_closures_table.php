<?php

declare(strict_types=1);

use App\Enums\ClosureReason;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_closures', function (Blueprint $table): void {
            $table->id();

            // UTC-stored, same reasoning as trainings.start_time/end_time
            // (Domain Model Spec §11.1).
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->enum('reason', array_column(ClosureReason::cases(), 'value'));
            $table->string('note', 255)->nullable();

            // Deliberately Restrict, not Cascade — narrower scope than the
            // User-deletion cascade on trainings/training_participants.
            // An admin who has created closures cannot be hard-deleted
            // (Disable remains available). See Domain Model Spec §4/§10
            // for the explicit note that this is open to revisiting.
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('ends_at');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_closures');
    }
};
