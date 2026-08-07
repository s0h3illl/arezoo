<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import DashboardTile from '@/components/admin/DashboardTile.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoment, formatToman } from '@/lib/format';
import type { DashboardSnapshot, Wish } from '@/types';

defineOptions({ layout: AdminLayout });

defineProps<{
    snapshot: DashboardSnapshot;
    latest_wishes: Wish[];
}>();
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="داشبورد | پنل مدیریت" />

        <div class="mx-auto w-full max-w-5xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                داشبورد
            </h1>
            <!--
                The daily-refresh clause used to live here, when the tiles were
                the whole screen. It belongs to the tiles alone now, and the
                «محاسبه‌شده در» line beneath them already carries it.
            -->
            <p class="mt-2 text-sm text-slate-500">نمای کلی پلتفرم.</p>

            <dl id="platform-totals" class="mt-6 grid gap-4 sm:grid-cols-3">
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

            <!--
                A glance at what has just been published, newest first: five
                rows, no paging and no way in. Nothing here is a link — there is
                no wishes section to reach.
            -->
            <section id="latest-wishes" class="mt-10">
                <h2 class="text-lg font-black text-slate-900">آرزوها</h2>

                <div
                    class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
                >
                    <div
                        v-if="latest_wishes.length > 0"
                        class="overflow-x-auto"
                    >
                        <table class="w-full min-w-[640px] text-right text-sm">
                            <thead
                                class="border-b border-slate-200 bg-slate-50 text-[13px] text-slate-500"
                            >
                                <tr>
                                    <th scope="col" class="px-5 py-3 font-bold">
                                        آرزو
                                    </th>
                                    <th scope="col" class="px-5 py-3 font-bold">
                                        صاحب آرزو
                                    </th>
                                    <th scope="col" class="px-5 py-3 font-bold">
                                        قیمت
                                    </th>
                                    <th scope="col" class="px-5 py-3 font-bold">
                                        زمان انتشار
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="wish in latest_wishes"
                                    :key="wish.id"
                                >
                                    <td
                                        class="px-5 py-4 font-bold text-slate-900"
                                    >
                                        {{ wish.title }}
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">
                                        {{ wish.owner.name }}
                                    </td>
                                    <!--
                                        Slate, not the emerald the raised tile
                                        uses. In this panel emerald means money
                                        that landed; a wish's price is what
                                        someone asked for, and the two must not
                                        read as the same kind of number.
                                    -->
                                    <td
                                        dir="ltr"
                                        class="px-5 py-4 text-left text-slate-600"
                                    >
                                        {{ formatToman(wish.price) }}
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">
                                        <time
                                            v-if="wish.created_at"
                                            :datetime="wish.created_at"
                                        >
                                            {{ formatMoment(wish.created_at) }}
                                        </time>
                                        <span v-else class="text-slate-400">
                                            نامشخص
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="px-6 py-14 text-center">
                        <p class="text-base font-bold text-slate-900">
                            هنوز آرزویی منتشر نشده
                        </p>
                        <p class="mt-2 text-sm text-slate-500">
                            به‌محض اینکه کسی آرزوش رو منتشر کنه، اینجا می‌بینیش.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
