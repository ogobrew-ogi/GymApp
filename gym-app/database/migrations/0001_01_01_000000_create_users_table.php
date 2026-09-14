<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');

            // Role model: strictly additive admin flag, not a separate
            // roles table — see Domain Model Spec §5.1 (UserRole enum is
            // derived from this column, never independently stored).
            $table->boolean('is_admin')->default(false);

            // Disabling (revocable, non-destructive) vs. deleting
            // (destructive, cascades — see §1/§6) are deliberately
            // different actions; this flag backs "Disable".
            $table->boolean('is_active')->default(true);

            // Forces the change-password screen immediately after any
            // admin-initiated account creation or password reset, since
            // there is no self-service "forgot password" flow at all.
            $table->boolean('must_change_password')->default(true);

            $table->timestamps();

            $table->index('is_active');
        });

        // Database-backed sessions (config/session.php: SESSION_DRIVER=database).
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
