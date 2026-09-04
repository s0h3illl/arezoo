<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import SubmitButton from '@/components/SubmitButton.vue';
import TextField from '@/components/TextField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { logout } from '@/routes';
import { update as updateAccount } from '@/routes/user-profile-information';
import { send } from '@/routes/verification';
import type { Account } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    user: Account;
    status?: string;
}>();

const emailSaved = computed(
    () => props.status === 'profile-information-updated',
);
</script>

<template>
    <main
        class="flex flex-1 items-center justify-center bg-[radial-gradient(ellipse_60%_50%_at_50%_20%,#d1fae5,transparent_70%)] px-4 py-10"
    >
        <Head title="تأیید ایمیل" />

        <div class="w-full max-w-[400px]">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                    ایمیلت رو تأیید کن
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    یک لینک تأیید برات فرستادیم. روی لینک بزن تا حسابت کامل بشه.
                </p>
            </div>

            <p
                v-if="status === 'verification-link-sent'"
                class="mb-4 rounded-[14px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-bold text-emerald-700"
            >
                لینک تأیید تازه‌ای برایتان ایمیل شد.
            </p>

            <Form :action="send()" class="auth-form" #default="{ processing }">
                <div
                    class="flex flex-col gap-1.5 rounded-[14px] bg-slate-50 px-4 py-3 text-center"
                >
                    <span class="text-[13px] font-bold text-slate-500">
                        لینک به این نشانی رفت
                    </span>
                    <span
                        dir="ltr"
                        class="text-[15px] font-bold text-slate-900"
                    >
                        {{ user.email }}
                    </span>
                </div>

                <SubmitButton :processing="processing">
                    ارسال دوباره‌ی لینک
                </SubmitButton>
            </Form>

            <section
                class="mt-6 rounded-[18px] border border-slate-200 bg-white p-5"
            >
                <h2 class="text-base font-black text-slate-900">
                    ایمیلت رو اشتباه زدی؟
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    نشانی درست رو بنویس تا لینک تأیید به همون‌جا برود.
                </p>

                <p
                    v-if="emailSaved"
                    data-test="email-saved"
                    class="mt-4 rounded-[14px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700"
                >
                    ایمیلت عوض شد.
                </p>

                <Form
                    #default="{ errors, processing }"
                    novalidate
                    :action="updateAccount.url()"
                    method="put"
                    error-bag="updateProfileInformation"
                    :options="{ preserveScroll: true }"
                    class="mt-4 flex flex-col gap-4"
                >
                    <input type="hidden" name="name" :value="user.name" />
                    <input
                        type="hidden"
                        name="username"
                        :value="user.username"
                    />

                    <TextField
                        id="email"
                        :model-value="user.email"
                        name="email"
                        label="ایمیل"
                        type="email"
                        dir="ltr"
                        placeholder="you@example.com"
                        autocomplete="email"
                        :error="errors.email"
                    />

                    <SubmitButton
                        data-test="save-email"
                        :processing="processing"
                    >
                        ذخیره‌ی ایمیل
                    </SubmitButton>
                </Form>
            </section>

            <p class="mt-6 text-center text-sm text-slate-500">
                یا از حسابت خارج شو و از نو شروع کن.
                <Link
                    :href="logout()"
                    as="button"
                    type="button"
                    class="font-bold text-emerald-600 hover:text-emerald-700"
                >
                    خروج از حساب
                </Link>
            </p>
        </div>
    </main>
</template>
