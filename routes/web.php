<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('terms', TermsController::class)->name('terms');

/*
| The parameter is `username`, not `user`: the panel's `user` binding resolves by
| primary key and leaves out admins, and an explicit binding cannot be given a
| custom key per route. A second name gets a second binding — see AppServiceProvider.
|
| The `:username` field is a hint, not the resolver. The explicit binding still
| does the resolving, and Laravel strips the field from the URI; it is here so
| Wayfinder types the generated helper's argument as the string it is, rather
| than falling back to the model's integer route key.
*/
Route::get('u/{username:username}', ProfileController::class)->name('profile');

/*
| A wish belongs to whoever created it, so nothing about the owner is in the URL
| or the body: `auth` names them and the wish is written through their own
| relation. There is no profile here to be a visitor to, so there is no second
| rule to enforce beyond being signed in.
*/
Route::post('wishes', [WishController::class, 'store'])
    ->middleware('auth')
    ->name('wishes.store');

/*
| Deleting names the wish, so unlike storing there is an owner to check against.
| `auth` still comes first, so a guest is sent to sign in rather than told a wish
| they cannot see exists.
*/
Route::delete('wishes/{wish}', [WishController::class, 'destroy'])
    ->middleware('auth')
    ->can('delete', 'wish')
    ->name('wishes.destroy');

require __DIR__.'/admin.php';
