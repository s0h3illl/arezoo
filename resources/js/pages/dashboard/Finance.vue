<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { formatNumber, formatToman } from '@/lib/format';
import type { Balance } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    balance: Balance;
    hold_hours: number;
}>();

const hasEarned = computed(() => props.balance.total > 0);

const holdHours = computed(() => formatNumber(props.hold_hours));
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
        </div>
    </main>
</template>
