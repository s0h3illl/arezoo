<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TermsController extends Controller
{
    /**
     * Show the terms of use.
     *
     * The copy is a file in the repository, so it is reviewed in a diff and
     * changes on deploy. The Markdown is converted here rather than in the
     * browser, which keeps a Markdown parser out of the bundle.
     *
     * A missing file is left to throw: it ships with the application, so its
     * absence is a broken deploy rather than a state the page should paper over.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Terms', [
            'body' => Str::markdown(File::get(resource_path('markdown/terms.md'))),
        ]);
    }
}
