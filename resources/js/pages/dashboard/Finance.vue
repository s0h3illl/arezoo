<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import SubmitButton from '@/components/SubmitButton.vue';
import TextField from '@/components/TextField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatMoment, formatNumber, formatToman } from '@/lib/format';
import { store } from '@/routes/dashboard/withdrawals';
import type { Balance, Withdrawal, WithdrawalStatus } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    balance: Balance;
    hold_hours: number;
    fee: number;
    minimum: number;
    withdrawals: Withdrawal[];
}>();

const statusLabels: Record<WithdrawalStatus, string> = {
    requested: 'در انتظار بررسی',
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

const hasEarned = computed(
    () => props.balance.total > 0 || props.withdrawals.length > 0,
);

const holdHours = computed(() => formatNumber(props.hold_hours));

const hasOpenRequest = computed(() =>
    props.withdrawals.some(
        (withdrawal) =>
            withdrawal.status === 'requested' ||
            withdrawal.status === 'accepted',
    ),
);

const canRequest = computed(
    () => !hasOpenRequest.value && props.balance.available >= props.minimum,
);

const form = useForm({
    amount: '',
    sheba: '',
});

const requestedAmount = computed(() => {
    const amount = Number(form.amount);

    return Number.isFinite(amount) ? Math.trunc(amount) : 0;
});

const payout = computed(() => Math.max(requestedAmount.value - props.fee, 0));

function takeEverything(): void {
    form.amount = String(props.balance.available);
}

function submit(): void {
    form.post(store.url(), { preserveScroll: true });
}

function payoutOf(withdrawal: Withdrawal): number {
    return withdrawal.amount - withdrawal.fee;
}
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="مالی" />

        <div class="mx-auto w-full max-w-2xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">مالی</h1>
            <p class="mt-2 text-sm text-slate-500">
                هرچی آرزوهات تا حالا برات جمع کردن، این‌جاست.
            </p>

            <section
                data-test="balance"
                class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <p class="text-sm font-bold text-slate-500">موجودی کل</p>
                <p
                    dir="ltr"
                    data-test="balance-total"
                    class="mt-1 text-left text-3xl font-black text-slate-900"
                >
                    {{ formatToman(balance.total) }}
                </p>

                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-emerald-50 px-5 py-4">
                        <dt class="text-sm font-bold text-emerald-700">
                            قابل برداشت
                        </dt>
                        <dd
                            dir="ltr"
                            data-test="balance-available"
                            class="mt-1 text-left text-xl font-black text-emerald-800"
                        >
                            {{ formatToman(balance.available) }}
                        </dd>
                    </div>

                    <div class="rounded-2xl bg-slate-50 px-5 py-4">
                        <dt class="text-sm font-bold text-slate-600">
                            نگه‌داشته‌شده
                        </dt>
                        <dd
                            dir="ltr"
                            data-test="balance-held"
                            class="mt-1 text-left text-xl font-black text-slate-800"
                        >
                            {{ formatToman(balance.held) }}
                        </dd>
                    </div>
                </dl>

                <p class="mt-4 text-xs text-slate-400">
                    پولی که به آرزوهات می‌رسه، {{ holdHours }} ساعت بعد از
                    پرداخت قابل برداشت می‌شه و تا اون موقع نگه داشته می‌شه. این
                    پول مال خودته و هیچ‌وقت از موجودی‌ت کم نمی‌شه.
                </p>
            </section>

            <p
                v-if="!hasEarned"
                data-test="no-earnings"
                class="mt-6 text-center text-sm text-slate-400"
            >
                هنوز پولی به موجودی‌ت اضافه نشده؛ به‌محض اینکه اولین کمک به
                آرزوهات برسه، این‌جا می‌بینیش.
            </p>

            <section
                data-test="withdrawal-request"
                class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <h2 class="text-lg font-black text-slate-900">
                    درخواست برداشت
                </h2>

                <p
                    v-if="hasOpenRequest"
                    data-test="open-request-notice"
                    class="mt-3 rounded-2xl bg-amber-50 px-5 py-4 text-sm text-amber-800"
                >
                    یک درخواست برداشت باز داری. تا وقتی تعیین تکلیف نشده،
                    نمی‌تونی درخواست تازه‌ای ثبت کنی.
                </p>

                <p
                    v-else-if="!canRequest"
                    data-test="nothing-to-request"
                    class="mt-3 rounded-2xl bg-slate-50 px-5 py-4 text-sm text-slate-500"
                >
                    موجودی قابل برداشتت باید دست‌کم
                    {{ formatToman(minimum) }} باشه تا بتونی درخواست بدی.
                </p>

                <form
                    v-else
                    class="mt-4 flex flex-col gap-4"
                    @submit.prevent="submit"
                >
                    <TextField
                        id="withdrawal-amount"
                        v-model="form.amount"
                        name="amount"
                        label="مبلغ درخواستی (تومان)"
                        type="number"
                        dir="ltr"
                        :max="balance.available"
                        :error="form.errors.amount"
                    >
                        <template #label-action>
                            <button
                                type="button"
                                data-test="take-everything"
                                class="text-[13px] font-bold text-emerald-600 hover:text-emerald-700"
                                @click="takeEverything"
                            >
                                همه‌ی موجودی ({{
                                    formatToman(balance.available)
                                }})
                            </button>
                        </template>
                    </TextField>

                    <TextField
                        id="withdrawal-sheba"
                        v-model="form.sheba"
                        name="sheba"
                        label="شماره شبا مقصد"
                        dir="ltr"
                        placeholder="IR000000000000000000000000"
                        :error="form.errors.sheba"
                    />

                    <dl
                        data-test="payout-preview"
                        class="grid gap-2 rounded-2xl bg-slate-50 px-5 py-4 text-sm"
                    >
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">کارمزد برداشت</dt>
                            <dd dir="ltr" class="font-bold text-slate-700">
                                {{ formatToman(fee) }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="font-bold text-slate-700">
                                مبلغی که به حسابت می‌رسه
                            </dt>
                            <dd
                                dir="ltr"
                                data-test="payout"
                                class="font-black text-emerald-700"
                            >
                                {{ formatToman(payout) }}
                            </dd>
                        </div>
                    </dl>

                    <SubmitButton :processing="form.processing">
                        ثبت درخواست
                    </SubmitButton>
                </form>
            </section>

            <section class="mt-6">
                <h2 class="text-lg font-black text-slate-900">
                    درخواست‌های برداشت
                </h2>

                <p
                    v-if="withdrawals.length === 0"
                    data-test="no-withdrawals"
                    class="mt-3 rounded-3xl border border-dashed border-slate-200 px-6 py-8 text-center text-sm text-slate-400"
                >
                    هنوز درخواست برداشتی ثبت نکردی؛ هر درخواستی که بدی، همین‌جا
                    با وضعیتش می‌مونه.
                </p>

                <ul v-else class="mt-3 flex flex-col gap-3">
                    <li
                        v-for="withdrawal in withdrawals"
                        :key="withdrawal.id"
                        data-test="withdrawal-row"
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <p
                                dir="ltr"
                                class="text-left text-lg font-black text-slate-900"
                            >
                                {{ formatToman(withdrawal.amount) }}
                            </p>
                            <span
                                class="shrink-0 rounded-full px-3 py-1 text-xs font-bold"
                                :class="statusBadgeClasses[withdrawal.status]"
                            >
                                {{ statusLabels[withdrawal.status] }}
                            </span>
                        </div>

                        <dl class="mt-4 grid gap-2 text-[13px] sm:grid-cols-2">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">کارمزد</dt>
                                <dd dir="ltr" class="font-bold text-slate-700">
                                    {{ formatToman(withdrawal.fee) }}
                                </dd>
                            </div>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">واریز به حساب</dt>
                                <dd dir="ltr" class="font-bold text-slate-700">
                                    {{ formatToman(payoutOf(withdrawal)) }}
                                </dd>
                            </div>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">شبا</dt>
                                <dd
                                    dir="ltr"
                                    class="font-bold text-slate-700 tabular-nums"
                                >
                                    {{ withdrawal.sheba }}
                                </dd>
                            </div>
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">تاریخ درخواست</dt>
                                <dd class="font-bold text-slate-700">
                                    <time :datetime="withdrawal.requested_at">
                                        {{
                                            formatMoment(
                                                withdrawal.requested_at,
                                            )
                                        }}
                                    </time>
                                </dd>
                            </div>
                            <div
                                v-if="withdrawal.decided_at"
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">
                                    تاریخ تعیین تکلیف
                                </dt>
                                <dd class="font-bold text-slate-700">
                                    <time :datetime="withdrawal.decided_at">
                                        {{
                                            formatMoment(withdrawal.decided_at)
                                        }}
                                    </time>
                                </dd>
                            </div>
                        </dl>
                    </li>
                </ul>
            </section>
        </div>
    </main>
</template>
