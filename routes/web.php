<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TermsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('terms', TermsController::class)->name('terms');

/*
| The parameter is `username`, not `user`: the panel's `user` binding resolves by
| primary key and leaves out admins, and an explicit binding cannot be given a
| custom key per route. A second name gets a second binding — see AppServiceProvider.
*/
Route::get('u/{username}', ProfileController::class)->name('profile');

require __DIR__.'/admin.php';
