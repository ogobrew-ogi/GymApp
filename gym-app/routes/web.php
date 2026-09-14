<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Every route in this application requires authentication — there are no
| public pages (Project Brief, "Authentication"). Admin routes (User
| Management, Gym Closures) are added as that feature is built.
|
*/

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Admin\GymClosures;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Auth\ChangePassword;
use App\Livewire\Auth\Login;
use App\Livewire\Profile;
use App\Livewire\Trainings\Create;
use App\Livewire\Trainings\Edit;
use App\Livewire\Trainings\History;
use App\Livewire\Trainings\Show;
use App\Livewire\Trainings\TrainingList;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');

    // Reachable even mid-forced-change, since it IS the forced-change screen.
    Route::get('/change-password', ChangePassword::class)->name('password.change');

    Route::middleware('password.change')->group(function (): void {
        Route::get('/', TrainingList::class)->name('trainings.index');
        Route::get('/trainings/create', Create::class)->name('trainings.create');
        Route::get('/trainings/{training}', Show::class)->name('trainings.show');
        Route::get('/trainings/{training}/edit', Edit::class)->name('trainings.edit');
        Route::get('/history', History::class)->name('history');

        Route::get('/profile', Profile::class)->name('profile');

        // Admin-only — 'can:viewAny,...' matches the same policy check the
        // Profile screen already uses to decide whether to show these
        // links at all (UX/UI Spec: admin features nested in Profile,
        // not a separate always-visible nav destination).
        Route::middleware('can:viewAny,App\Models\User')->group(function (): void {
            Route::get('/admin/users', UserManagement::class)->name('admin.users.index');
        });

        Route::middleware('can:viewAny,App\Models\GymClosure')->group(function (): void {
            Route::get('/admin/closures', GymClosures::class)->name('admin.closures.index');
        });
    });
});
