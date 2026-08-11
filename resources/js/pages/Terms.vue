<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps<{
    /**
     * The document as HTML, converted server-side from
     * `resources/markdown/terms.md` — see `App\Http\Controllers\TermsController`.
     */
    body: string;
}>();
</script>

<template>
    <main class="flex-1">
        <Head title="قوانین و مقررات" />

        <div
            class="mx-auto w-full max-w-5xl px-4 pt-8 pb-12 sm:px-6 sm:pt-12 sm:pb-16 lg:px-8 lg:pt-14 lg:pb-18"
        >
            <p
                class="text-[13px] font-extrabold tracking-widest text-emerald-500"
            >
                قوانین و مقررات
            </p>

            <h1
                class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl lg:text-4xl"
            >
                شرایط استفاده از آرزو
            </h1>

            <div
                class="legal-prose mt-8 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 lg:p-7"
                v-html="body"
            />
        </div>
    </main>
</template>
