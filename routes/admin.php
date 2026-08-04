<?php

use Illuminate\Support\Facades\Route;

// TODO: no admin-role system exists yet (see CONTEXT.md's "Admin" entry) —
// this route is intentionally unprotected until real authorization lands.
Route::inertia('/admin', 'admin/Dashboard')->name('admin.dashboard');
