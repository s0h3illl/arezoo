<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import TextField from '@/components/TextField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request as passwordRequest } from '@/routes/password';

defineOptions({ layout: AppLayout });

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
});

function submit(): void {
    form.clearErrors();

    if (form.email.trim() === '') {
        form.setError('email', 'ایمیلت رو وارد کن.');
    }

    if (form.password.length < 8) {
        form.setError('password', 'رمز عبور باید حداقل ۸ کاراکتر باشه.');
    }

    if (form.hasErrors) {
        return;
    }

    form.post(store.url(), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <main
        class="flex flex-1 items-center justify-center bg-[radial-gradient(ellipse_60%_50%_at_50%_20%,#d1fae5,transparent_70%)] px-4 py-10"
    >
        <Head title="ورود" />

        <div class="w-full max-w-[400px]">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                    خوش برگشتی!
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    وارد شو تا لیست آرزوهات منتظرت نمونن.
                </p>
            </div>

            <p
                v-if="status"
                class="mb-4 rounded-[14px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-bold text-emerald-700"
            >
                {{ status }}
            </p>

            <form novalidate class="auth-form" @submit.prevent="submit">
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
                    autocomplete="current-password"
                    :error="form.errors.password"
                >
                    <template #label-action>
                        <Link
                            :href="passwordRequest()"
                            class="text-xs font-bold text-emerald-600 hover:text-emerald-700"
                        >
                            فراموش کردی؟
                        </Link>
                    </template>
                </TextField>

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
                    <span v-else>ورود</span>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                حساب نداری؟
                <Link
                    :href="register()"
                    class="font-bold text-emerald-600 hover:text-emerald-700"
                >
                    ثبت‌نام کن
                </Link>
                — رایگانه!
            </p>
        </div>
    </main>
</template>
