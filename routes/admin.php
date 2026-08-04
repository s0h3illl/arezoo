<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
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
        Route::get('users', [UserController::class, 'index'])->name('users.index');
    });
