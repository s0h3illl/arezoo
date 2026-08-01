<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import SubmitButton from '@/components/SubmitButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({ layout: AppLayout });

defineProps<{
    email: string;
    status?: string;
}>();
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
                        {{ email }}
                    </span>
                </div>

                <SubmitButton :processing="processing">
                    ارسال دوباره‌ی لینک
                </SubmitButton>
            </Form>

            <p class="mt-6 text-center text-sm text-slate-500">
                ایمیلت رو اشتباه زدی؟
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
