<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { terms } from '@/routes';
</script>

<template>
    <footer class="mt-auto border-t border-slate-200 bg-white">
        <div
            class="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-6 sm:px-6 lg:px-8"
        >
            <div class="flex items-center gap-2">
                <span
                    aria-hidden="true"
                    class="flex size-7 items-center justify-center rounded-lg bg-emerald-500 text-sm font-black text-white"
                >
                    آ
                </span>
                <span class="text-base font-black text-slate-900">آرزو</span>
            </div>

            <p class="text-xs text-slate-400">
                آرزوهات رو بنویس، بقیه‌ش با ماست.
            </p>

            <Link
                :href="terms()"
                class="text-xs font-bold text-emerald-600 hover:text-emerald-700"
            >
                قوانین و مقررات
            </Link>
        </div>
    </footer>
</template>
