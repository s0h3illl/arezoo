<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configures if and how Inertia uses Server Side Rendering
    | to pre-render each initial request made to your application's pages
    | so that server rendered HTML is delivered for the user's browser.
    |
    | See: https://inertiajs.com/server-side-rendering
    |
    */

    'ssr' => [
        /*
         * Off unless something turns it on. The development stack sets
         * INERTIA_SSR_ENABLED=true, because there the Vite dev server answers the
         * render request itself.
         *
         * Left on unconditionally, this would be true in production too, where no
         * such server exists. That combination happens to be harmless — the gateway
         * notices it is not running hot, finds no bundle, and quietly returns null
         * so the browser renders the page — but it is harmless by accident, and it
         * costs an HTTP request per page render to discover it. It also means a
         * half-finished SSR deployment looks like it is working.
         *
         * To turn SSR on for real: run `bun run build:ssr` at build time, keep
         * `bundle` below pointing at the file it writes, run a process that serves
         * it, and set INERTIA_SSR_URL to that process.
         */
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', false),

        // The SSR server is started by the asset container, so the address of it is
        // a property of where this process is running rather than a constant: the
        // loopback default is right for a single-machine run, and the compose stack
        // overrides it with the service name that actually resolves across containers.
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),

        // The build writes here (`bun run build:ssr` outputs to bootstrap/ssr). Named
        // rather than left to autodetection so that a build which fails to produce it
        // disables SSR outright instead of leaving the gateway to guess.
        'bundle' => base_path('bootstrap/ssr/ssr.mjs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | These options configure how Inertia discovers page components on the
    | filesystem. The paths and extensions are used to locate components
    | when rendering responses and during testing assertions.
    |
    */

    'pages' => [

        'paths' => [
            resource_path('js/pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | The values described here are used to locate Inertia components on the
    | filesystem. For instance, when using `assertInertia`, the assertion
    | attempts to locate the component as a file relative to the paths.
    |
    */

    'testing' => [

        'ensure_pages_exist' => true,

    ],

];
