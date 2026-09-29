<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { terms } from '@/routes';
</script>

<template>
    <footer class="mt-auto border-t border-slate-200 bg-white">
        <div
            class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
        >
            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-2">
                    <img src="/logo.svg" alt="" class="size-7" />
                    <span class="text-base font-black text-slate-900"
                        >آرزو</span
                    >
                </div>

                <p class="text-sm text-slate-500">
                    آرزوهات رو بنویس، بقیه‌ش با ماست.
                </p>

                <Link
                    :href="terms()"
                    class="w-fit text-sm font-bold text-emerald-600 hover:text-emerald-700"
                >
                    قوانین و مقررات
                </Link>
            </div>

            <a
                href="https://trustseal.enamad.ir/?id=7772908&Code=I6VHz9qUku8j8dLHv5kTFhqMQwfYru80"
                target="_blank"
                rel="noopener"
                referrerpolicy="origin"
                class="flex size-[88px] shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white p-1.5 transition-colors hover:border-emerald-200 focus-visible:border-emerald-200 focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none"
            >
                <img
                    src="/enmad-icon.png"
                    width="88"
                    height="88"
                    loading="lazy"
                    decoding="async"
                    alt="نماد اعتماد الکترونیکی"
                    class="size-full object-contain"
                />
            </a>
        </div>
    </footer>
</template>
