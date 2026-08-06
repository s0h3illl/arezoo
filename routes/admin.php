<?php

use App\Http\Controllers\Admin\ContributionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserPasswordController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

/*
| The admin check stands alone — no `auth` in front of it. A guest gets the same
| 404 a non-admin does, rather than a login redirect that would give the section
| away to anyone who guessed the URL.
*/
Route::middleware(EnsureUserIsAdmin::class)
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('users', UserController::class)->only(['index', 'show', 'update']);
        Route::put('users/{user}/password', [UserPasswordController::class, 'update'])
            ->name('users.password.update');
        Route::resource('contributions', ContributionController::class)->only(['index']);
        Route::resource('payments', PaymentController::class)->only(['index']);
    });
