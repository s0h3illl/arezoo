<script setup lang="ts">
import { Head, InfiniteScroll } from '@inertiajs/vue3';
import { ProgressIndicator, ProgressRoot } from 'reka-ui';
import { computed } from 'vue';

import CheckIcon from '@/components/icons/CheckIcon.vue';
import LinkIcon from '@/components/icons/LinkIcon.vue';
import ContributorRowItem from '@/components/wish/ContributorRow.vue';
import { setHeaderNav } from '@/composables/useHeaderNav';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatNumber, formatShare, formatToman, shareOf } from '@/lib/format';
import { profile } from '@/routes';
import type { Contribution, Paginated, User, Wish } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    wish: Wish;
    owner: User;
    contributions: Paginated<Contribution>;
}>();

setHeaderNav([
    { label: 'برگشت به پروفایل', href: profile(props.owner.username).url },
]);

const isFunded = computed(() => props.wish.received >= props.wish.price);

const remaining = computed(() =>
    Math.max(props.wish.price - props.wish.received, 0),
);

const barValue = computed(() =>
    Math.min(props.wish.received, props.wish.price),
);

/** Clamped above, so the bar never passes full width; the percentage is not. */
const barWidth = computed(
    () => `${shareOf(barValue.value, props.wish.price) * 100}%`,
);

const purchaseHost = computed(() => {
    if (props.wish.purchase_link === null) {
        return '';
    }

    try {
        return new URL(props.wish.purchase_link).host;
    } catch {
        return props.wish.purchase_link;
    }
});
</script>

<template>
    <main class="flex-1">
        <Head :title="wish.title" />

        <div
            class="mx-auto grid w-full max-w-[1040px] grid-cols-[repeat(auto-fit,minmax(320px,1fr))] items-start gap-[clamp(18px,3vw,28px)] px-[clamp(16px,4vw,32px)] pt-[clamp(20px,4vw,32px)] pb-[clamp(40px,6vw,64px)]"
        >
            <article
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <div class="relative">
                    <img
                        v-if="wish.thumbnail"
                        :src="wish.thumbnail"
                        alt=""
                        class="h-[clamp(220px,32vw,320px)] w-full object-cover"
                    />
                    <div
                        v-else
                        aria-hidden="true"
                        class="h-[clamp(220px,32vw,320px)] w-full wish-cover-placeholder"
                    ></div>

                    <p
                        v-if="isFunded"
                        class="absolute start-3.5 top-3.5 flex items-center gap-1 rounded-full bg-emerald-600 px-3 py-1 text-[12.5px] font-black text-white shadow-[0_4px_12px_rgb(5_150_105/0.4)] [&>svg]:size-[13px]"
                    >
                        <CheckIcon />
                        برآورده شد!
                    </p>
                </div>

                <div class="flex flex-col gap-4 p-[clamp(20px,4vw,28px)]">
                    <h1
                        class="text-[clamp(21px,3.5vw,26px)] leading-normal font-black text-slate-900"
                    >
                        {{ wish.title }}
                    </h1>

                    <div class="rounded-2xl bg-slate-50 p-4 sm:p-5">
                        <div
                            class="flex flex-wrap items-baseline justify-between gap-2"
                        >
                            <p
                                class="text-[clamp(20px,3vw,25px)] font-black text-emerald-700"
                            >
                                {{ formatNumber(wish.received) }}
                                <span class="text-sm font-bold text-slate-500">
                                    از {{ formatToman(wish.price) }}
                                </span>
                            </p>
                            <p
                                class="text-[clamp(20px,3vw,25px)] font-black text-emerald-600"
                            >
                                {{ formatShare(wish.received, wish.price) }}
                            </p>
                        </div>

                        <ProgressRoot
                            :model-value="barValue"
                            :max="wish.price"
                            class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-slate-200"
                        >
                            <ProgressIndicator
                                class="h-full rounded-full bg-emerald-500 transition-[width] duration-500"
                                :style="{ width: barWidth }"
                            />
                        </ProgressRoot>

                        <p class="mt-2 text-[12.5px] font-bold text-slate-500">
                            {{
                                isFunded
                                    ? 'تکمیل شد'
                                    : `${formatToman(remaining)} مونده`
                            }}
                        </p>
                    </div>

                    <p
                        v-if="wish.description"
                        class="text-[14.5px] leading-loose whitespace-pre-wrap text-slate-600"
                    >
                        {{ wish.description }}
                    </p>

                    <a
                        v-if="wish.purchase_link"
                        :href="wish.purchase_link"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 transition-colors hover:border-emerald-200 hover:bg-emerald-50"
                    >
                        <div
                            class="flex size-[38px] shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 [&>svg]:size-[18px]"
                        >
                            <LinkIcon />
                        </div>
                        <div class="flex items-center justify-between w-full">
                            <span
                                class="text-[13.5px] font-extrabold text-slate-900"
                            >
                                لینک خرید محصول
                            </span>
                            <span class="truncate text-xs text-slate-400">
                                {{ purchaseHost }}
                            </span>
                        </div>
                    </a>
                </div>
            </article>

            <section
                class="rounded-3xl border border-slate-200 bg-white p-[clamp(20px,4vw,28px)] shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-black text-slate-900">
                        کمک‌کننده‌ها
                    </h2>
                    <p
                        class="rounded-full bg-slate-100 px-3 py-1 text-[12.5px] font-extrabold text-slate-600"
                    >
                        {{ formatNumber(contributions.meta.total) }} مشارکت
                    </p>
                </div>

                <p
                    v-if="contributions.data.length === 0"
                    class="mt-6 text-center text-sm text-slate-400"
                >
                    هنوز کسی کمک نکرده
                </p>

                <!-- `items-element` keeps the scroll triggers out of the list. -->
                <InfiniteScroll
                    v-else
                    data="contributions"
                    items-element="#contributor-list"
                >
                    <ul id="contributor-list" class="mt-2">
                        <ContributorRowItem
                            v-for="row in contributions.data"
                            :key="row.id"
                            :row="row"
                        />
                    </ul>
                </InfiniteScroll>
            </section>
        </div>
    </main>
</template>
