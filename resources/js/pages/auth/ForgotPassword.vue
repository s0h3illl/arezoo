<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { email as sendResetLink } from '@/routes/password';

defineOptions({ layout: AppLayout });

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

function inputClasses(hasError: boolean): string {
    return cn(
        'w-full rounded-[14px] border bg-slate-50 px-4 py-[13px] text-left text-[15px] text-slate-900 placeholder:text-slate-400 focus:bg-white focus:ring-[3px] focus:outline-none',
        hasError
            ? 'border-red-400 ring-[3px] ring-red-500/15'
            : 'border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/15',
    );
}

function submit(): void {
    form.clearErrors();

    if (form.email.trim() === '') {
        form.setError('email', 'ایمیلت رو وارد کن.');

        return;
    }

    form.post(sendResetLink.url());
}
</script>

<template>
    <main
        class="flex flex-1 items-center justify-center bg-[radial-gradient(ellipse_60%_50%_at_50%_20%,#d1fae5,transparent_70%)] px-4 py-10"
    >
        <Head title="فراموشی رمز عبور" />

        <div class="w-full max-w-[400px]">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                    رمزت رو فراموش کردی؟
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    ایمیلت رو بنویس تا لینک بازیابی رمز عبور رو برات بفرستیم.
                </p>
            </div>

            <p
                v-if="status"
                class="mb-4 rounded-[14px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-bold text-emerald-700"
            >
                {{ status }}
            </p>

            <form
                novalidate
                class="flex flex-col gap-[18px] rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)] sm:p-8"
                @submit.prevent="submit"
            >
                <div class="flex flex-col gap-1.5">
                    <label
                        for="email"
                        class="text-[13px] font-bold text-slate-700"
                    >
                        ایمیل
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        dir="ltr"
                        placeholder="you@example.com"
                        autocomplete="username"
                        :aria-invalid="Boolean(form.errors.email)"
                        :aria-describedby="
                            form.errors.email ? 'email-error' : undefined
                        "
                        :class="inputClasses(Boolean(form.errors.email))"
                    />
                    <p
                        v-if="form.errors.email"
                        id="email-error"
                        class="text-xs text-red-600"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

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
                    <span v-else>ارسال لینک بازیابی</span>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                رمزت یادت اومد؟
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
