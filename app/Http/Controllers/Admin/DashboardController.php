<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DashboardSnapshot;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin panel's landing screen: a once-a-day snapshot of the
     * platform's headline numbers.
     */
    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'snapshot' => DashboardSnapshot::current(),
        ]);
    }
}
