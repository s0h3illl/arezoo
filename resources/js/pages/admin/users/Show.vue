<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import SubmitButton from '@/components/SubmitButton.vue';
import TextField from '@/components/TextField.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { index, update } from '@/routes/admin/users';
import { update as updatePassword } from '@/routes/admin/users/password';
import type { User } from '@/types';

defineOptions({ layout: AdminLayout });

/**
 * One user in full, mirroring `App\Http\Resources\Admin\UserResource`.
 *
 * Derived from `User` rather than written out again: a field that changes shape
 * cannot come to mean one thing here and another everywhere else.
 */
type AdminUserDetail = Omit<User, 'is_admin' | 'updated_at'>;

const props = defineProps<{ user: AdminUserDetail }>();

/**
 * The first date the app shows anyone.
 *
 * `fa-IR` gives the Jalali calendar the reader actually keeps, and it comes with
 * the browser — a date library would be a dependency for a single line. The
 * machine-readable original stays on the `<time>` element beside it.
 */
const registration = computed(() => {
    if (!props.user.created_at) {
        return null;
    }

    return {
        iso: props.user.created_at,
        label: new Intl.DateTimeFormat('fa-IR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        }).format(new Date(props.user.created_at)),
    };
});

/**
 * Block or unblock this user.
 *
 * The browser's own dialog is the confirmation step, as it is on the list —
 * blocking reaches a real person, so it is never one click.
 */
function toggleBlock(): void {
    const question = props.user.is_blocked
        ? `مسدودی ${props.user.name} برداشته بشه؟`
        : `${props.user.name} مسدود بشه؟ از این به بعد نمی‌تونه وارد بشه.`;

    if (!window.confirm(question)) {
        return;
    }

    router.patch(
        update.url(props.user.id),
        { action: 'block', is_blocked: !props.user.is_blocked },
        { preserveScroll: true },
    );
}

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

/**
 * Set a new password for this user, after a confirmation step.
 *
 * The way back in for someone who has lost access to their email. The
 * dialog names the concrete consequence — the old password stops working —
 * rather than the session machinery behind it, mirroring the block dialog's
 * plain-language style.
 */
function submitPassword(): void {
    if (
        !window.confirm(
            `برای ${props.user.name} رمز عبور تازه تنظیم بشه؟ با رمز قبلی‌ش دیگه نمی‌تونه وارد بشه.`,
        )
    ) {
        return;
    }

    // Reset only on success, unlike the public reset-password form: a
    // rejected password stays on screen so the admin can see and fix it,
    // rather than retyping it blind for someone who isn't present to help.
    passwordForm.put(updatePassword.url(props.user.id), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head :title="`${user.name} | پنل مدیریت`" />

        <div class="mx-auto w-full max-w-3xl">
            <Link
                :href="index.url()"
                class="text-sm font-bold text-slate-500 transition-colors hover:text-slate-900"
            >
                بازگشت به کاربران
            </Link>

            <div
                class="mt-4 flex flex-wrap items-start justify-between gap-4 sm:items-center"
            >
                <div>
                    <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                        {{ user.name }}
                    </h1>
                    <p
                        dir="ltr"
                        class="mt-2 text-left text-sm text-slate-500 sm:text-right"
                    >
                        {{ user.email }}
                    </p>
                </div>

                <button
                    type="button"
                    :class="[
                        'rounded-full px-4 py-2 text-sm font-bold transition-colors focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none',
                        user.is_blocked
                            ? 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                            : 'bg-red-50 text-red-700 hover:bg-red-100',
                    ]"
                    @click="toggleBlock"
                >
                    {{ user.is_blocked ? 'رفع مسدودی' : 'مسدود کردن' }}
                </button>
            </div>

            <dl
                class="mt-6 grid gap-px overflow-hidden rounded-3xl border border-slate-200 bg-slate-200 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)] sm:grid-cols-2"
            >
                <div class="bg-white px-6 py-5">
                    <dt class="text-[13px] font-bold text-slate-500">ایمیل</dt>
                    <dd
                        dir="ltr"
                        class="mt-1 text-left font-bold text-slate-900"
                    >
                        {{ user.email }}
                    </dd>
                </div>

                <div class="bg-white px-6 py-5">
                    <dt class="text-[13px] font-bold text-slate-500">
                        تأیید ایمیل
                    </dt>
                    <dd class="mt-1">
                        <span
                            v-if="user.email_verified_at"
                            class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"
                        >
                            تأیید‌شده
                        </span>
                        <span
                            v-else
                            class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700"
                        >
                            تأیید‌نشده
                        </span>
                    </dd>
                </div>

                <div class="bg-white px-6 py-5">
                    <dt class="text-[13px] font-bold text-slate-500">
                        تاریخ عضویت
                    </dt>
                    <dd class="mt-1 font-bold text-slate-900">
                        <time v-if="registration" :datetime="registration.iso">
                            {{ registration.label }}
                        </time>
                        <span v-else class="text-slate-400">نامشخص</span>
                    </dd>
                </div>

                <!--
                    A field on a detail view cannot be left blank, so the
                    unblocked state is said in the negative rather than given a
                    word of its own. `CONTEXT.md` names no opposite of Blocked,
                    and this screen is not the place to invent one.
                -->
                <div class="bg-white px-6 py-5">
                    <dt class="text-[13px] font-bold text-slate-500">وضعیت</dt>
                    <dd class="mt-1">
                        <span
                            v-if="user.is_blocked"
                            class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700"
                        >
                            مسدود
                        </span>
                        <span v-else class="font-bold text-slate-900">
                            مسدود نیست
                        </span>
                    </dd>
                </div>
            </dl>

            <!--
                For a user who has lost access to their email and therefore
                cannot use the password-reset flow themselves.
            -->
            <div
                class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <h2 class="text-lg font-black text-slate-900">
                    تنظیم رمز عبور جدید
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    برای کاربری که به ایمیلش دسترسی نداره و نمی‌تونه از مسیر
                    بازیابی رمز عبور استفاده کنه.
                </p>

                <form
                    novalidate
                    class="mt-4 flex flex-col gap-4"
                    @submit.prevent="submitPassword"
                >
                    <TextField
                        id="password"
                        v-model="passwordForm.password"
                        label="رمز عبور جدید"
                        type="password"
                        dir="ltr"
                        autocomplete="new-password"
                        :error="passwordForm.errors.password"
                    />

                    <!--
                        No `error` passed here: Laravel's `confirmed` rule
                        always reports a mismatch on `password`, never on
                        `password_confirmation`, so the message renders above
                        and this field carries no error state of its own.
                    -->
                    <TextField
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        label="تکرار رمز عبور جدید"
                        type="password"
                        dir="ltr"
                        autocomplete="new-password"
                    />

                    <SubmitButton :processing="passwordForm.processing">
                        تنظیم رمز عبور
                    </SubmitButton>
                </form>
            </div>
        </div>
    </main>
</template>
