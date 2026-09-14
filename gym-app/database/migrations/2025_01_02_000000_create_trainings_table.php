<?php

declare(strict_types=1);

use App\Enums\TrainingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table): void {
            $table->id();

            // Ownership is immutable after creation and never transferred
            // (Architecture §5.7). Cascade: deleting the owner deletes
            // every training they own, regardless of history (accepted
            // decision, Domain Model Spec §1/§6) — this is a genuinely
            // destructive path, distinct from disabling a user.
            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Denormalized alongside start_time/end_time purely to make
            // the Home Screen's "Today / Tomorrow / [date]" grouping a
            // direct, index-friendly lookup (Domain Model Spec §2).
            $table->date('date');

            // Stored in UTC; converted to/from Europe/Sofia only at the
            // application boundary (Domain Model Spec §11.1), so overlap
            // comparisons stay correct across DST transitions.
            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->unsignedSmallInteger('max_participants');

            // Plain text only, no markup persisted regardless of input
            // (UX/UI Spec §13); ~200 char cap enforced at the validation
            // layer, column sized with headroom.
            $table->string('note', 255)->nullable();

            $table->enum('status', array_column(TrainingStatus::cases(), 'value'))
                ->default(TrainingStatus::Scheduled->value);

            $table->timestamps();

            // Every list/query in the app filters by status first.
            $table->index('status');

            // Supports both "all scheduled, chronological" and the
            // overlap-check query in one composite index.
            $table->index(['status', 'start_time']);

            $table->index('date');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
