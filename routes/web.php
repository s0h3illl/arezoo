<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TermsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('terms', TermsController::class)->name('terms');

require __DIR__.'/admin.php';
