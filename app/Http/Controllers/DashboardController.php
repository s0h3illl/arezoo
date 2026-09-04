<?php

namespace App\Http\Controllers;

use App\Http\Resources\AccountResource;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'user' => new AccountResource(auth()->user()),
        ]);
    }
}
