<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ContributionResource;
use App\Models\Contribution;
use Inertia\Inertia;
use Inertia\Response;

class ContributionController extends Controller
{
    /**
     * List every contribution, newest first, so an admin can trace money
     * through the app. Read-only: there is no store, update, or destroy here.
     */
    public function index(): Response
    {
        $contributions = Contribution::query()
            ->with(['wish', 'contributor'])
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('admin/contributions/Index', [
            'contributions' => ContributionResource::collection($contributions),
        ]);
    }
}
