<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import DashboardTile from '@/components/admin/DashboardTile.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoment, formatToman } from '@/lib/format';
import type { DashboardSnapshot } from '@/types';

defineOptions({ layout: AdminLayout });

defineProps<{
    snapshot: DashboardSnapshot;
}>();
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="داشبورد | پنل مدیریت" />

        <div class="mx-auto w-full max-w-5xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                داشبورد
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                نمای کلی پلتفرم؛ حداکثر یک بار در روز به‌روزرسانی می‌شه.
            </p>

            <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                <DashboardTile label="کاربران">
                    {{ snapshot.users_count }}
                </DashboardTile>

                <DashboardTile label="آرزوها">
                    {{ snapshot.wishes_count }}
                </DashboardTile>

                <DashboardTile label="مجموع جمع‌آوری‌شده" accent="emerald">
                    {{ formatToman(snapshot.raised_amount) }}
                </DashboardTile>
            </dl>

            <p class="mt-4 text-xs text-slate-400">
                محاسبه‌شده در
                <time :datetime="snapshot.computed_at">{{
                    formatMoment(snapshot.computed_at)
                }}</time>
            </p>
        </div>
    </main>
</template>
