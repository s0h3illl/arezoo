<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';

import PaginationLink from '@/components/admin/PaginationLink.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoment, formatToman } from '@/lib/format';
import { update } from '@/routes/admin/withdrawals';
import type { Paginated, WithdrawalStatus } from '@/types';
import type { Withdrawal } from '@/types/admin';

defineOptions({ layout: AdminLayout });

defineProps<{
    withdrawals: Paginated<Withdrawal>;
}>();

const statusLabels: Record<WithdrawalStatus, string> = {
    requested: 'در انتظار',
    accepted: 'تأییدشده',
    paid: 'پرداخت‌شده',
    rejected: 'ردشده',
};

const statusBadgeClasses: Record<WithdrawalStatus, string> = {
    requested: 'bg-amber-50 text-amber-700',
    accepted: 'bg-sky-50 text-sky-700',
    paid: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-rose-50 text-rose-700',
};

function accept(withdrawal: Withdrawal): void {
    if (!window.confirm(`درخواست برداشت ${withdrawal.owner.name} تأیید بشه؟`)) {
        return;
    }

    router.patch(
        update.url(withdrawal.id),
        { action: 'accept' },
        { preserveScroll: true },
    );
}

function markPaid(withdrawal: Withdrawal): void {
    if (
        !window.confirm(
            `پرداخت برداشت ${withdrawal.owner.name} انجام شده و ثبت بشه؟ بعد از این قابل تغییر نیست.`,
        )
    ) {
        return;
    }

    router.patch(
        update.url(withdrawal.id),
        { action: 'pay' },
        { preserveScroll: true },
    );
}

function reject(withdrawal: Withdrawal): void {
    if (
        !window.confirm(
            `درخواست برداشت ${withdrawal.owner.name} رد بشه؟ مبلغش به موجودی قابل برداشتش برمی‌گرده.`,
        )
    ) {
        return;
    }

    router.patch(
        update.url(withdrawal.id),
        { action: 'reject' },
        { preserveScroll: true },
    );
}
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="برداشت‌ها | پنل مدیریت" />

        <div class="mx-auto w-full max-w-7xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                برداشت‌ها
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                {{ withdrawals.meta.total }} درخواست برداشت روی پلتفرم.
            </p>

            <div
                class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <div v-if="withdrawals.data.length > 0" class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-right text-sm">
                        <thead
                            class="border-b border-slate-200 bg-slate-50 text-[13px] text-slate-500"
                        >
                            <tr>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    مالک
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    مبلغ درخواستی
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    کارمزد
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    مبلغ واریز
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    شبا
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    تاریخ درخواست
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    تاریخ تعیین تکلیف
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    وضعیت
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    عملیات
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="withdrawal in withdrawals.data"
                                :key="withdrawal.id"
                                data-test="withdrawal-row"
                            >
                                <td class="px-5 py-4 font-bold text-slate-900">
                                    {{ withdrawal.owner.name }}
                                </td>
                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-left text-slate-600"
                                >
                                    {{ formatToman(withdrawal.amount) }}
                                </td>
                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-left text-slate-600"
                                >
                                    {{ formatToman(withdrawal.fee) }}
                                </td>
                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-left font-bold text-slate-900"
                                >
                                    {{ formatToman(withdrawal.transfer) }}
                                </td>
                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-left text-slate-600 tabular-nums"
                                >
                                    {{ withdrawal.sheba }}
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <time :datetime="withdrawal.requested_at">
                                        {{
                                            formatMoment(
                                                withdrawal.requested_at,
                                            )
                                        }}
                                    </time>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <time
                                        v-if="withdrawal.decided_at"
                                        :datetime="withdrawal.decided_at"
                                    >
                                        {{
                                            formatMoment(withdrawal.decided_at)
                                        }}
                                    </time>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                            statusBadgeClasses[
                                                withdrawal.status
                                            ],
                                        ]"
                                    >
                                        {{ statusLabels[withdrawal.status] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="
                                                withdrawal.status ===
                                                'requested'
                                            "
                                            type="button"
                                            data-test="accept"
                                            class="rounded-full bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-100 focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none"
                                            @click="accept(withdrawal)"
                                        >
                                            تأیید
                                        </button>
                                        <button
                                            v-else-if="
                                                withdrawal.status === 'accepted'
                                            "
                                            type="button"
                                            data-test="mark-paid"
                                            class="rounded-full bg-sky-50 px-4 py-2 text-xs font-bold text-sky-700 transition-colors hover:bg-sky-100 focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none"
                                            @click="markPaid(withdrawal)"
                                        >
                                            ثبت پرداخت
                                        </button>

                                        <button
                                            v-if="
                                                withdrawal.status ===
                                                    'requested' ||
                                                withdrawal.status === 'accepted'
                                            "
                                            type="button"
                                            data-test="reject"
                                            class="rounded-full bg-rose-50 px-4 py-2 text-xs font-bold text-rose-700 transition-colors hover:bg-rose-100 focus-visible:ring-2 focus-visible:ring-rose-500/40 focus-visible:outline-none"
                                            @click="reject(withdrawal)"
                                        >
                                            رد
                                        </button>
                                        <span
                                            v-if="
                                                withdrawal.status === 'paid' ||
                                                withdrawal.status === 'rejected'
                                            "
                                            class="text-slate-400"
                                            >—</span
                                        >
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-14 text-center">
                    <p class="text-base font-bold text-slate-900">
                        هنوز درخواست برداشتی ثبت نشده
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        به‌محض اینکه کسی درخواست برداشت بده، این‌جا می‌بینیش.
                    </p>
                </div>
            </div>

            <nav
                v-if="withdrawals.meta.last_page > 1"
                aria-label="صفحه‌بندی برداشت‌ها"
                class="mt-6 flex items-center justify-between gap-4"
            >
                <PaginationLink :href="withdrawals.links.prev">
                    صفحه‌ی قبل
                </PaginationLink>

                <p class="text-sm text-slate-500">
                    صفحه‌ی {{ withdrawals.meta.current_page }} از
                    {{ withdrawals.meta.last_page }}
                </p>

                <PaginationLink :href="withdrawals.links.next">
                    صفحه‌ی بعد
                </PaginationLink>
            </nav>
        </div>
    </main>
</template>
