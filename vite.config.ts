import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

/**
 * Where the browser reaches the dev server, when it is not the browser's own
 * machine. Vite's defaults are correct for `bun run dev` on the host and wrong for
 * the compose stack, where the dev server listens inside a container and the
 * browser is outside it.
 *
 * The origin is the address that ends up in the hot file, and therefore the address
 * the browser requests every asset from — so it has to be the host-published URL,
 * not the address the container binds. HMR follows from it: a websocket is opened
 * from the browser, so it needs the same host and port the browser is already
 * using, not the internal one.
 */
const devHost = process.env.VITE_DEV_SERVER_HOST;
const devOrigin = process.env.VITE_DEV_SERVER_ORIGIN;
const hmr = devOrigin ? new URL(devOrigin) : null;

export default defineConfig({
    server: {
        host: devHost,
        origin: devOrigin,
        hmr: hmr
            ? {
                  host: hmr.hostname,
                  clientPort: Number(hmr.port || (hmr.protocol === 'https:' ? 443 : 80)),
              }
            : undefined,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Vazirmatn', {
                    weights: [400, 500, 700, 800, 900],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
});
