<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import SubmitButton from '@/components/SubmitButton.vue';
import TextAreaField from '@/components/TextAreaField.vue';
import TextField from '@/components/TextField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { update as updatePassword } from '@/routes/user-password';
import { update as updateAccount } from '@/routes/user-profile-information';
import type { Account } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    user: Account;
    status?: string;
}>();

const accountSaved = computed(
    () => props.status === 'profile-information-updated',
);

const passwordSaved = computed(() => props.status === 'password-updated');

const accountForm = useForm({
    name: props.user.name,
    username: props.user.username,
    email: props.user.email,
    bio: props.user.bio ?? '',
});

function saveAccount(): void {
    accountForm.put(updateAccount.url(), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
    });
}

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function changePassword(): void {
    passwordForm.put(updatePassword.url(), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="اطلاعات من" />

        <div class="mx-auto w-full max-w-2xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                اطلاعات من
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                هرچیزی که روی صفحه‌ی عمومی‌ت دیده می‌شه، از همین‌جا عوض می‌شه.
            </p>

            <section
                class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <h2 class="text-lg font-black text-slate-900">مشخصات من</h2>

                <p
                    v-if="accountSaved"
                    data-test="account-saved"
                    class="mt-4 rounded-[14px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700"
                >
                    اطلاعاتت ذخیره شد.
                </p>

                <form
                    novalidate
                    class="mt-4 flex flex-col gap-4"
                    @submit.prevent="saveAccount"
                >
                    <TextField
                        id="name"
                        v-model="accountForm.name"
                        label="نام"
                        type="text"
                        placeholder="اسمت رو بنویس"
                        autocomplete="name"
                        :error="accountForm.errors.name"
                    />

                    <TextField
                        id="username"
                        v-model="accountForm.username"
                        label="نام کاربری"
                        type="text"
                        dir="ltr"
                        placeholder="sara-ahmadi"
                        autocomplete="nickname"
                        :error="accountForm.errors.username"
                    />

                    <TextField
                        id="email"
                        v-model="accountForm.email"
                        label="ایمیل"
                        type="email"
                        dir="ltr"
                        placeholder="you@example.com"
                        autocomplete="email"
                        :error="accountForm.errors.email"
                    />

                    <TextAreaField
                        id="bio"
                        v-model="accountForm.bio"
                        label="درباره‌ی من"
                        placeholder="یک جمله درباره‌ی خودت بنویس"
                        :error="accountForm.errors.bio"
                    />

                    <SubmitButton
                        data-test="save-account"
                        :processing="accountForm.processing"
                    >
                        ذخیره
                    </SubmitButton>
                </form>
            </section>

            <section
                class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <h2 class="text-lg font-black text-slate-900">تغییر رمز عبور</h2>
                <p class="mt-1 text-sm text-slate-500">
                    برای عوض کردن رمز، اول رمز فعلی‌ت رو بزن.
                </p>

                <p
                    v-if="passwordSaved"
                    data-test="password-saved"
                    class="mt-4 rounded-[14px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700"
                >
                    رمز عبورت عوض شد.
                </p>

                <form
                    novalidate
                    class="mt-4 flex flex-col gap-4"
                    @submit.prevent="changePassword"
                >
                    <TextField
                        id="current_password"
                        v-model="passwordForm.current_password"
                        label="رمز عبور فعلی"
                        type="password"
                        dir="ltr"
                        autocomplete="current-password"
                        :error="passwordForm.errors.current_password"
                    />

                    <TextField
                        id="password"
                        v-model="passwordForm.password"
                        label="رمز عبور جدید"
                        type="password"
                        dir="ltr"
                        autocomplete="new-password"
                        :error="passwordForm.errors.password"
                    />

                    <TextField
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        label="تکرار رمز عبور جدید"
                        type="password"
                        dir="ltr"
                        autocomplete="new-password"
                    />

                    <SubmitButton
                        data-test="change-password"
                        :processing="passwordForm.processing"
                    >
                        تغییر رمز عبور
                    </SubmitButton>
                </form>
            </section>
        </div>
    </main>
</template>
