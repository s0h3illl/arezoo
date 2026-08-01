<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import SubmitButton from '@/components/SubmitButton.vue';
import TextField from '@/components/TextField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { update } from '@/routes/password';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    email?: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(update.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <main
        class="flex flex-1 items-center justify-center bg-[radial-gradient(ellipse_60%_50%_at_50%_20%,#d1fae5,transparent_70%)] px-4 py-10"
    >
        <Head title="بازیابی رمز عبور" />

        <div class="w-full max-w-[400px]">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                    یک رمز تازه بساز
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    یک رمز عبور جدید انتخاب کن تا دوباره وارد بشی.
                </p>
            </div>

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
                    label="رمز عبور جدید"
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
                    label="تکرار رمز عبور جدید"
                    type="password"
                    dir="ltr"
                    autocomplete="new-password"
                />

                <SubmitButton :processing="form.processing">
                    تغییر رمز عبور
                </SubmitButton>
            </form>
        </div>
    </main>
</template>
