<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="داشبورد | پنل مدیریت" />

        <div class="mx-auto w-full max-w-5xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                داشبورد
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                بقیه بخش‌ها به‌زودی اضافه می‌شن.
            </p>

            <div
                class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)] sm:p-8"
            >
                <p class="text-lg font-semibold text-slate-900">سلام دنیا 👋</p>
            </div>
        </div>
    </main>
</template>
