<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { home } from '@/routes';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    status: number;
}>();

/**
 * The statuses the exception hook routes here; anything it does not route keeps
 * Laravel's own page. The hook holds its own copy of that list, so the fallback
 * below is what a status added on one side but not the other lands on — a blank
 * page rather than a vague apology is the worse of the two failures.
 */
const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'اجازه‌ی دسترسی نداری',
        description: 'این صفحه برای حساب تو باز نیست.',
    },
    404: {
        title: 'این صفحه پیدا نشد',
        description: 'شاید نشانی را اشتباه زده باشی یا صفحه برداشته شده باشد.',
    },
    429: {
        title: 'درخواست‌هایت بیش از حد مجاز است',
        description: 'کمی صبر کن و بعد دوباره تلاش کن.',
    },
    500: {
        title: 'مشکلی پیش آمد',
        description: 'خطا از سمت ماست. لطفاً کمی بعد دوباره تلاش کن.',
    },
    503: {
        title: 'سرویس موقتاً در دسترس نیست',
        description: 'در حال رسیدگی به سایت هستیم. به‌زودی برمی‌گردیم.',
    },
};

const message = computed(
    () =>
        messages[props.status] ?? {
            title: 'مشکلی پیش آمد',
            description: 'لطفاً کمی بعد دوباره تلاش کن.',
        },
);
</script>

<template>
    <main
        class="flex flex-1 items-center justify-center bg-[radial-gradient(ellipse_60%_50%_at_50%_20%,#d1fae5,transparent_70%)] px-4 py-10"
    >
        <Head :title="message.title" />

        <div class="w-full max-w-[400px] text-center">
            <p dir="ltr" class="text-6xl font-black text-emerald-600">
                {{ status }}
            </p>

            <h1 class="mt-4 text-2xl font-black text-slate-900 sm:text-3xl">
                {{ message.title }}
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                {{ message.description }}
            </p>

            <Link
                :href="home()"
                class="mt-6 inline-flex items-center justify-center rounded-[14px] bg-emerald-600 px-6 py-3 text-[15px] font-bold text-white transition-colors hover:bg-emerald-700"
            >
                بازگشت به صفحه‌ی اصلی
            </Link>
        </div>
    </main>
</template>
