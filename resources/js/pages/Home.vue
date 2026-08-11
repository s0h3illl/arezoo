<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

import GiftIcon from '@/components/icons/GiftIcon.vue';
import PlusIcon from '@/components/icons/PlusIcon.vue';
import ShareIcon from '@/components/icons/ShareIcon.vue';
import SparkleIcon from '@/components/icons/SparkleIcon.vue';
import { setHeaderNav } from '@/composables/useHeaderNav';
import AppLayout from '@/layouts/AppLayout.vue';
import { register } from '@/routes';
import type { Faq } from '@/types';

defineOptions({ layout: AppLayout });

defineProps<{
    faqs: Faq[];
}>();

/** These anchors only resolve here, so this is the page that owns them. */
setHeaderNav([
    { label: 'امکانات', href: '#features' },
    { label: 'سوالات', href: '#faq' },
]);

/*
 * Marketing copy, so it lives in the page rather than the database — unlike the
 * questions below it, which is the one part of this page that changes without a
 * deploy.
 */
const features: { icon: Component; title: string; body: string }[] = [
    {
        icon: PlusIcon,
        title: 'ساخت لیست در چند ثانیه',
        body: 'یک عنوان، یک قیمت، یک لینک. نه فرم طولانی، نه مرحله‌ی اضافه.',
    },
    {
        icon: ShareIcon,
        title: 'اشتراک با یک لینک',
        body: 'یک لینک داری که همه‌جا کار می‌کنه؛ کسی که بازش می‌کنه لازم نیست حساب بسازه.',
    },
    {
        icon: GiftIcon,
        title: 'رزرو مخفیانه‌ی هدیه',
        body: 'هر کسی می‌تونه مخفیانه توی خرید یک آرزو مشارکت کنه و اسمش جایی دیده نشه.',
    },
    {
        icon: SparkleIcon,
        title: 'برای هر مناسبتی',
        body: 'تولد، سالگرد، فارغ‌التحصیلی یا هیچ مناسبتی. لیستت همیشه باز و آماده‌ست.',
    },
];

const steps: { number: string; title: string; body: string }[] = [
    {
        number: '۱',
        title: 'لیستت رو بساز',
        body: 'حسابت رو بساز و در چند ثانیه اولین لیست آرزوهات رو داشته باش.',
    },
    {
        number: '۲',
        title: 'آرزوهات رو اضافه کن',
        body: 'هر چیزی که دوستش داری، از هر فروشگاهی، با لینک یا بدون لینک.',
    },
    {
        number: '۳',
        title: 'لینک رو بفرست',
        body: 'لینکت رو برای دوست‌ها و خانواده بفرست و بقیه‌ش با ماست.',
    },
];
</script>

<template>
    <div class="flex flex-1 flex-col">
        <Head title="لیست آرزوها برای دوست‌ها و خانواده" />

        <main>
            <section class="bg-radial from-emerald-100 to-transparent">
                <div
                    class="mx-auto flex w-full max-w-3xl flex-col items-center px-4 py-10 text-center sm:px-6 sm:py-16 lg:px-8 lg:py-20"
                >
                    <p
                        class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-xs font-bold text-emerald-700"
                    >
                        <span
                            aria-hidden="true"
                            class="size-2 rounded-full bg-emerald-500"
                        ></span>
                        برای دوست‌ها و خانواده
                    </p>

                    <h1
                        class="mt-6 text-4xl leading-snug font-black text-slate-900 sm:text-5xl lg:text-6xl"
                    >
                        آرزوهات رو بنویس،<br />بقیه‌ش با ماست
                    </h1>

                    <p
                        class="mt-5 max-w-lg text-base leading-loose text-slate-600 sm:text-lg"
                    >
                        لیست آرزوهات رو بساز، لینکش رو برای دوست‌ها و خانواده
                        بفرست و بذار توی خرید چیزی که واقعاً می‌خوای کمکت کنن.
                    </p>

                    <div
                        class="mt-8 flex flex-wrap items-center justify-center gap-3"
                    >
                        <Link
                            :href="register()"
                            class="flex h-12 items-center rounded-xl bg-emerald-500 px-7 text-base font-bold text-white shadow-lg shadow-emerald-500/40 transition-colors hover:bg-emerald-600"
                        >
                            ساخت اولین لیست
                        </Link>
                        <a
                            href="#how"
                            class="flex h-12 items-center rounded-xl border border-slate-200 bg-white px-7 text-base font-bold text-slate-600 transition-colors hover:border-emerald-200 hover:text-emerald-700"
                        >
                            چطور کار می‌کنه؟
                        </a>
                    </div>
                </div>
            </section>

            <section
                id="features"
                class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8 lg:py-16"
            >
                <p class="text-center text-xs font-extrabold text-emerald-500">
                    امکانات
                </p>
                <h2
                    class="mt-2 text-center text-2xl font-black text-slate-900 sm:text-3xl"
                >
                    هدیه دادن، بدون حدس زدن
                </h2>

                <div
                    class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-2xl border border-slate-200 bg-white p-6"
                    >
                        <span
                            class="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                        >
                            <component :is="feature.icon" />
                        </span>
                        <h3 class="mt-4 text-lg font-extrabold text-slate-900">
                            {{ feature.title }}
                        </h3>
                        <p class="mt-2 text-sm leading-loose text-slate-500">
                            {{ feature.body }}
                        </p>
                    </article>
                </div>
            </section>

            <!-- The only section on white, so the band reads as a step out of the page. -->
            <section id="how" class="border-y border-slate-200 bg-white">
                <div
                    class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8 lg:py-16"
                >
                    <h2
                        class="text-center text-2xl font-black text-slate-900 sm:text-3xl"
                    >
                        چطور کار می‌کنه؟
                    </h2>

                    <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-3">
                        <div
                            v-for="step in steps"
                            :key="step.number"
                            class="flex flex-col items-center text-center"
                        >
                            <span
                                aria-hidden="true"
                                class="flex size-14 items-center justify-center rounded-full bg-emerald-500 text-2xl font-black text-white"
                            >
                                {{ step.number }}
                            </span>
                            <h3
                                class="mt-4 text-lg font-extrabold text-slate-900"
                            >
                                {{ step.title }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-loose text-slate-500"
                            >
                                {{ step.body }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!--
                A native `details` accordion: no JavaScript, so the answers are
                readable the moment the markup lands and stay readable if the
                bundle never does.
            -->
            <section
                id="faq"
                class="mx-auto w-full max-w-4xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8 lg:py-16"
            >
                <h2
                    class="text-center text-2xl font-black text-slate-900 sm:text-3xl"
                >
                    سوال‌های پرتکرار
                </h2>

                <div class="mt-10 flex flex-col gap-3">
                    <details
                        v-for="(faq, index) in faqs"
                        :key="faq.id"
                        :open="index === 0"
                        class="group rounded-2xl border border-slate-200 bg-white px-6 py-5"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-bold text-slate-900 [&::-webkit-details-marker]:hidden"
                        >
                            {{ faq.title }}
                            <span
                                aria-hidden="true"
                                class="text-xl text-emerald-500 transition-transform group-open:rotate-45"
                            >
                                ＋
                            </span>
                        </summary>
                        <p class="mt-3 text-sm leading-loose text-slate-500">
                            {{ faq.body }}
                        </p>
                    </details>
                </div>
            </section>

            <section
                class="mx-auto w-full max-w-5xl px-4 pb-10 sm:px-6 sm:pb-14 lg:px-8 lg:pb-16"
            >
                <div
                    class="rounded-3xl bg-emerald-500 px-6 py-8 text-center sm:py-12 lg:py-14"
                >
                    <h2 class="text-2xl font-black text-white sm:text-3xl">
                        آماده‌ای اولین لیستت رو بسازی؟
                    </h2>
                    <p class="mt-3 text-base text-emerald-100">
                        رایگانه و کمتر از یک دقیقه طول می‌کشه.
                    </p>
                    <Link
                        :href="register()"
                        class="mt-7 inline-flex h-12 items-center rounded-xl bg-white px-7 text-base font-bold text-emerald-700 transition-colors hover:bg-emerald-50"
                    >
                        شروع رایگان
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
