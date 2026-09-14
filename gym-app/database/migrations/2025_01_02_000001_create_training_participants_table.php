<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_participants', function (Blueprint $table): void {
            $table->id();

            // Deleting a Training (owner solo-delete, or cascaded from an
            // owner being deleted) removes its participant rows with it.
            $table->foreignId('training_id')
                ->constrained('trainings')
                ->cascadeOnDelete();

            // Deleting a User removes their participation on any other
            // training too, freeing the slot (accepted decision — Domain
            // Model Spec §1/§3/§6, "don't mind training history").
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('joined_at')->useCurrent();

            // Deliberately absent: no `is_owner` flag (derivable from
            // trainings.owner_id, storing it would risk drift — §5.4),
            // no `status` (joining is immediate and unconditional).

            // Structurally makes duplicate participation impossible —
            // this is the constraint, not just an app-level check.
            $table->unique(['training_id', 'user_id']);

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_participants');
    }
};
