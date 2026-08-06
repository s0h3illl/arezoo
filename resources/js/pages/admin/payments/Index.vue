<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';

import PaginationLink from '@/components/admin/PaginationLink.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatMoment, formatToman } from '@/lib/format';
import { index } from '@/routes/admin/payments';
import type { Paginated, Payment, PaymentStatus } from '@/types';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    payments: Paginated<Payment>;
    filters: { search: string };
}>();

const search = ref(props.filters.search);

let debounceTimer: ReturnType<typeof setTimeout> | undefined;

/**
 * Always from page one — an identifier that matched nothing on page three would
 * look like a payment that never happened rather than an empty page.
 */
function runSearch(): void {
    clearTimeout(debounceTimer);

    router.get(
        index.url(),
        { search: search.value.trim() },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

/** Typing searches a beat after the admin stops; Enter does not wait for it. */
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(runSearch, 300);
});

onBeforeUnmount(() => clearTimeout(debounceTimer));

/**
 * An attempt that reached the gateway and never came back reads differently
 * from one the gateway turned down — the first may still land, the second
 * never will, and the money behind them is a different conversation.
 */
const statusLabels: Record<PaymentStatus, string> = {
    pending: 'در انتظار درگاه',
    verified: 'تأییدشده',
    failed: 'ناموفق',
};

const statusBadgeClasses: Record<PaymentStatus, string> = {
    pending: 'bg-amber-50 text-amber-700',
    verified: 'bg-emerald-50 text-emerald-700',
    failed: 'bg-red-50 text-red-700',
};
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="پرداخت‌ها | پنل مدیریت" />

        <div class="mx-auto w-full max-w-6xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                پرداخت‌ها
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                {{ payments.meta.total }} تلاش پرداخت از درگاه، موفق و ناموفق.
            </p>

            <!--
                The way in for "I paid and nothing happened": the identifier the
                gateway showed the user is the only thing they have to hand.
            -->
            <form
                novalidate
                role="search"
                class="mt-6"
                @submit.prevent="runSearch"
            >
                <label for="search" class="sr-only">
                    جست‌وجوی پرداخت با شناسه تراکنش
                </label>
                <input
                    id="search"
                    v-model="search"
                    type="search"
                    name="search"
                    dir="ltr"
                    class="field-input text-left"
                    placeholder="جست‌وجو با شناسه تراکنش"
                />
            </form>

            <div
                class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <div v-if="payments.data.length > 0" class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-right text-sm">
                        <thead
                            class="border-b border-slate-200 bg-slate-50 text-[13px] text-slate-500"
                        >
                            <tr>
                                <!--
                                    "تراکنش" and "پیگیری" are the gateway's own
                                    words for its two identifiers, kept so an
                                    admin can read them straight off the
                                    gateway's dashboard. `CONTEXT.md` avoids
                                    "transaction" for a payment, not for the
                                    identifier the gateway hands back.
                                -->
                                <th scope="col" class="px-5 py-3 font-bold">
                                    شناسه تراکنش
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    شناسه پیگیری
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    مبلغ
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    وضعیت
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    زمان تلاش
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    مشارکت
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="payment in payments.data"
                                :key="payment.id"
                            >
                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-left font-bold text-slate-900"
                                >
                                    {{ payment.transaction_id ?? '—' }}
                                </td>
                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-left text-slate-600"
                                >
                                    {{ payment.reference_id ?? '—' }}
                                </td>
                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-left text-slate-600"
                                >
                                    {{ formatToman(payment.amount) }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                                            statusBadgeClasses[payment.status],
                                        ]"
                                    >
                                        {{ statusLabels[payment.status] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <time
                                        v-if="payment.created_at"
                                        :datetime="payment.created_at"
                                    >
                                        {{ formatMoment(payment.created_at) }}
                                    </time>
                                    <span v-else class="text-slate-400">
                                        نامشخص
                                    </span>
                                </td>
                                <!--
                                    A failed attempt has no contribution to
                                    show: the row was deleted when the payment
                                    failed (ADR-0001), and this line is the only
                                    place that says so.
                                -->
                                <td class="px-5 py-4">
                                    <template v-if="payment.contribution">
                                        <span class="font-bold text-slate-900">
                                            {{
                                                payment.contribution.wish.title
                                            }}
                                        </span>
                                        <span
                                            class="mt-1 block text-[13px] text-slate-500"
                                        >
                                            {{
                                                payment.contribution.contributor
                                                    .name
                                            }}
                                        </span>
                                    </template>
                                    <span v-else class="text-slate-400">
                                        مشارکتی ازش نمونده
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!--
                    A search that found nothing is not an empty platform, and
                    saying so is the difference between "this user never paid"
                    and "you mistyped the identifier".
                -->
                <div v-else-if="filters.search" class="px-6 py-14 text-center">
                    <p class="text-base font-bold text-slate-900">
                        پرداختی با این شناسه پیدا نشد
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        جست‌وجو روی شناسه تراکنش انجام می‌شه — همونی که درگاه به
                        کاربر نشون داده.
                    </p>
                </div>

                <div v-else class="px-6 py-14 text-center">
                    <p class="text-base font-bold text-slate-900">
                        هنوز پرداختی ثبت نشده
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        به‌محض اینکه کسی پرداختی رو شروع کنه، چه به نتیجه برسه
                        چه نرسه، اینجا می‌بینیش.
                    </p>
                </div>
            </div>

            <nav
                v-if="payments.meta.last_page > 1"
                aria-label="صفحه‌بندی پرداخت‌ها"
                class="mt-6 flex items-center justify-between gap-4"
            >
                <PaginationLink :href="payments.links.prev">
                    صفحه‌ی قبل
                </PaginationLink>

                <p class="text-sm text-slate-500">
                    صفحه‌ی {{ payments.meta.current_page }} از
                    {{ payments.meta.last_page }}
                </p>

                <PaginationLink :href="payments.links.next">
                    صفحه‌ی بعد
                </PaginationLink>
            </nav>
        </div>
    </main>
</template>
