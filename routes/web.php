<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('terms', TermsController::class)->name('terms');

Route::get('dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::get('dashboard/messages', InboxController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard.messages');

Route::delete('profile/avatar', [ProfileAvatarController::class, 'destroy'])
    ->middleware('auth')
    ->name('profile.avatar.destroy');

Route::get('u/{username:username}', ProfileController::class)->name('profile');

Route::post('wishes', [WishController::class, 'store'])
    ->middleware('auth')
    ->name('wishes.store');

Route::get('wishes/{wish}', [WishController::class, 'show'])->name('wishes.show');

Route::put('wishes/{wish}', [WishController::class, 'update'])
    ->middleware('auth')
    ->can('update', 'wish')
    ->name('wishes.update');

Route::delete('wishes/{wish}', [WishController::class, 'destroy'])
    ->middleware('auth')
    ->can('delete', 'wish')
    ->name('wishes.destroy');

require __DIR__.'/admin.php';
