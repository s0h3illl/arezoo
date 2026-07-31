<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import TextField from '@/components/TextField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({ layout: AppLayout });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(store.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <main
        class="flex flex-1 items-center justify-center bg-[radial-gradient(ellipse_60%_50%_at_50%_20%,#d1fae5,transparent_70%)] px-4 py-10"
    >
        <Head title="ثبت‌نام" />

        <div class="w-full max-w-[400px]">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                    بزن بریم!
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    یک حساب بساز و اولین آرزوت رو ثبت کن.
                </p>
            </div>

            <form novalidate class="auth-form" @submit.prevent="submit">
                <TextField
                    id="name"
                    v-model="form.name"
                    label="نام"
                    type="text"
                    placeholder="اسمت رو بنویس"
                    autocomplete="name"
                    :error="form.errors.name"
                />

                <TextField
                    id="email"
                    v-model="form.email"
                    label="ایمیل"
                    type="email"
                    dir="ltr"
                    placeholder="you@example.com"
                    autocomplete="username"
                    :error="form.errors.email"
                />

                <TextField
                    id="password"
                    v-model="form.password"
                    label="رمز عبور"
                    type="password"
                    dir="ltr"
                    autocomplete="new-password"
                    :error="form.errors.password"
                />

                <!--
                    No `error` passed here: Laravel's `confirmed` rule always
                    reports a mismatch on `password`, never on
                    `password_confirmation`, so the message renders above
                    and this field carries no error state of its own.
                -->
                <TextField
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    label="تکرار رمز عبور"
                    type="password"
                    dir="ltr"
                    autocomplete="new-password"
                />

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex h-12 w-full items-center justify-center rounded-[14px] bg-emerald-500 text-base font-extrabold text-white shadow-[0_10px_24px_-8px_rgb(16_185_129/0.5)] transition-colors hover:bg-emerald-600 disabled:opacity-60"
                >
                    <svg
                        v-if="form.processing"
                        aria-hidden="true"
                        class="size-5 animate-spin"
                        viewBox="0 0 24 24"
                        fill="none"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"
                        />
                    </svg>
                    <span v-else>ثبت‌نام</span>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                قبلاً حساب ساختی؟
                <Link
                    :href="login()"
                    class="font-bold text-emerald-600 hover:text-emerald-700"
                >
                    وارد شو
                </Link>
            </p>
        </div>
    </main>
</template>
