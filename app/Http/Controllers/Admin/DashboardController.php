<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin panel's landing screen.
     */
    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard');
    }
}
