<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { AvatarFallback, AvatarImage, AvatarRoot } from 'reka-ui';
import { computed } from 'vue';

import FileField from '@/components/FileField.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextAreaField from '@/components/TextAreaField.vue';
import TextField from '@/components/TextField.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy as destroyAvatar } from '@/routes/profile/avatar';
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

const initial = computed(() => [...props.user.name][0] ?? '');

function removeAvatar(): void {
    router.delete(destroyAvatar.url(), {
        preserveScroll: true,
        preserveState: true,
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

                <Form
                    #default="{ errors, processing }"
                    novalidate
                    :action="updateAccount.url()"
                    method="post"
                    error-bag="updateProfileInformation"
                    :options="{ preserveScroll: true }"
                    class="mt-4 flex flex-col gap-4"
                >
                    <input type="hidden" name="_method" value="put" />

                    <div class="flex items-center gap-4">
                        <AvatarRoot
                            class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-50"
                        >
                            <AvatarImage
                                v-if="user.avatar"
                                :src="user.avatar"
                                alt=""
                                class="size-full rounded-full border-1 border-gray-100 object-cover shadow"
                            />
                            <AvatarFallback
                                class="text-2xl font-black text-emerald-600"
                            >
                                {{ initial }}
                            </AvatarFallback>
                        </AvatarRoot>

                        <p
                            v-if="!user.avatar"
                            data-test="no-avatar"
                            class="text-xs font-bold text-slate-400"
                        >
                            هنوز عکسی انتخاب نکردی.
                        </p>

                        <button
                            v-if="user.avatar"
                            type="button"
                            data-test="remove-avatar"
                            class="rounded-lg px-2 py-1.5 text-xs font-bold text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                            @click="removeAvatar"
                        >
                            حذف عکس
                        </button>
                    </div>

                    <FileField
                        id="avatar"
                        name="avatar"
                        label="عکس من"
                        accept="image/*"
                        :hint="
                            user.avatar
                                ? 'اگر عکسی انتخاب کنی، جای عکس فعلی می‌شینه. حداکثر ۵۱۲ کیلوبایت.'
                                : 'اختیاری. حداکثر ۵۱۲ کیلوبایت.'
                        "
                        :error="errors.avatar"
                    />

                    <TextField
                        id="name"
                        :model-value="user.name"
                        name="name"
                        label="نام"
                        type="text"
                        placeholder="اسمت رو بنویس"
                        autocomplete="name"
                        :error="errors.name"
                    />

                    <TextField
                        id="username"
                        :model-value="user.username"
                        name="username"
                        label="نام کاربری"
                        type="text"
                        dir="ltr"
                        placeholder="sara-ahmadi"
                        autocomplete="nickname"
                        :error="errors.username"
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

                    <TextAreaField
                        id="bio"
                        :model-value="user.bio ?? ''"
                        name="bio"
                        label="درباره‌ی من"
                        placeholder="یک جمله درباره‌ی خودت بنویس"
                        :error="errors.bio"
                    />

                    <SubmitButton
                        data-test="save-account"
                        :processing="processing"
                    >
                        ذخیره
                    </SubmitButton>
                </Form>
            </section>

            <section
                class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <h2 class="text-lg font-black text-slate-900">
                    تغییر رمز عبور
                </h2>
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

                <Form
                    #default="{ errors, processing }"
                    novalidate
                    :action="updatePassword.url()"
                    method="put"
                    error-bag="updatePassword"
                    :options="{ preserveScroll: true }"
                    class="mt-4 flex flex-col gap-4"
                    reset-on-success
                >
                    <TextField
                        id="current_password"
                        name="current_password"
                        label="رمز عبور فعلی"
                        type="password"
                        dir="ltr"
                        autocomplete="current-password"
                        :error="errors.current_password"
                    />

                    <TextField
                        id="password"
                        name="password"
                        label="رمز عبور جدید"
                        type="password"
                        dir="ltr"
                        autocomplete="new-password"
                        :error="errors.password"
                    />

                    <TextField
                        id="password_confirmation"
                        name="password_confirmation"
                        label="تکرار رمز عبور جدید"
                        type="password"
                        dir="ltr"
                        autocomplete="new-password"
                    />

                    <SubmitButton
                        data-test="change-password"
                        :processing="processing"
                    >
                        تغییر رمز عبور
                    </SubmitButton>
                </Form>
            </section>
        </div>
    </main>
</template>
