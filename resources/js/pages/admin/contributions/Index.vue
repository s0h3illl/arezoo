<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PaginationLink from '@/components/admin/PaginationLink.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoment, formatToman } from '@/lib/format';
import type { Paginated } from '@/types';
import type { Contribution, ContributionStatus } from '@/types/admin';

defineOptions({ layout: AdminLayout });

defineProps<{
    contributions: Paginated<Contribution>;
}>();

const statusLabels: Record<ContributionStatus, string> = {
    paid: 'پرداخت‌شده',
    pending: 'در انتظار',
};

const statusBadgeClasses: Record<ContributionStatus, string> = {
    paid: 'bg-emerald-50 text-emerald-700',
    pending: 'bg-amber-50 text-amber-700',
};
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="مشارکت‌ها | پنل مدیریت" />

        <div class="mx-auto w-full max-w-6xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                مشارکت‌ها
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                {{ contributions.meta.total }} مشارکت روی پلتفرم.
            </p>

            <div
                class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <div
                    v-if="contributions.data.length > 0"
                    class="overflow-x-auto"
                >
                    <table class="w-full min-w-[900px] text-right text-sm">
                        <thead
                            class="border-b border-slate-200 bg-slate-50 text-[13px] text-slate-500"
                        >
                            <tr>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    آرزو
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    مشارکت‌کننده
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    مبلغ
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    وضعیت
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    زمان تسویه
                                </th>
                                <!--
                                    The gateway attempt behind the money: its
                                    transaction identifier, with the reference
                                    the gateway handed back beneath it. Both, so
                                    a row here can be matched against the
                                    gateway's own dashboard.
                                -->
                                <th scope="col" class="px-5 py-3 font-bold">
                                    پرداخت
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="contribution in contributions.data"
                                :key="contribution.id"
                            >
                                <td class="px-5 py-4 font-bold text-slate-900">
                                    {{ contribution.wish.title }}
                                    <span
                                        v-if="contribution.wish.deleted"
                                        class="font-normal text-slate-400"
                                    >
                                        (حذف‌شده)
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <span v-if="contribution.contributor">
                                        {{ contribution.contributor.name }}
                                    </span>
                                    <span v-else class="text-slate-400">
                                        کاربر حذف‌شده
                                    </span>
                                </td>
                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-left text-slate-600"
                                >
                                    {{ formatToman(contribution.amount) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                            statusBadgeClasses[
                                                contribution.status
                                            ],
                                        ]"
                                    >
                                        {{ statusLabels[contribution.status] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <time
                                        v-if="contribution.settled_at"
                                        :datetime="contribution.settled_at"
                                    >
                                        {{
                                            formatMoment(
                                                contribution.settled_at,
                                            )
                                        }}
                                    </time>
                                    <span v-else class="text-slate-400">
                                        هنوز تسویه نشده
                                    </span>
                                </td>
                                <td dir="ltr" class="px-5 py-4 text-left">
                                    <span class="font-bold text-slate-900">
                                        {{
                                            contribution.payment
                                                .transaction_id ?? '—'
                                        }}
                                    </span>
                                    <span
                                        class="mt-1 block text-[13px] text-slate-500"
                                    >
                                        {{
                                            contribution.payment.reference_id ??
                                            '—'
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-14 text-center">
                    <p class="text-base font-bold text-slate-900">
                        هنوز مشارکتی ثبت نشده
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        به‌محض اینکه کسی به آرزویی کمک کنه، اینجا می‌بینیش.
                    </p>
                </div>
            </div>

            <nav
                v-if="contributions.meta.last_page > 1"
                aria-label="صفحه‌بندی مشارکت‌ها"
                class="mt-6 flex items-center justify-between gap-4"
            >
                <PaginationLink :href="contributions.links.prev">
                    صفحه‌ی قبل
                </PaginationLink>

                <p class="text-sm text-slate-500">
                    صفحه‌ی {{ contributions.meta.current_page }} از
                    {{ contributions.meta.last_page }}
                </p>

                <PaginationLink :href="contributions.links.next">
                    صفحه‌ی بعد
                </PaginationLink>
            </nav>
        </div>
    </main>
</template>
