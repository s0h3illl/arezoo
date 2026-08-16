<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';

import PaginationLink from '@/components/admin/PaginationLink.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { index, show, update } from '@/routes/admin/users';
import type { User } from '@/types/admin';
import type { Paginated } from '@/types';

defineOptions({ layout: AdminLayout });

/*
 * The panel's own `User`, from `@/types/admin`, mirroring
 * `App\Http\Resources\Admin\UserResource`. It is not derived from the public
 * `User` in `@/types`: that one carries only what the app is willing to publish
 * to any page, and an email address and a blocked flag are exactly what it
 * leaves out. `created_at` arrives with the rest — the resource also feeds the
 * detail view — and this screen deliberately leaves it unread.
 */
const props = defineProps<{
    users: Paginated<User>;
    filters: { search: string };
}>();

const search = ref(props.filters.search);

let debounceTimer: ReturnType<typeof setTimeout> | undefined;

/**
 * Always from page one — a term that matched nothing on page three would look
 * like an empty platform rather than an empty page.
 */
function runSearch(): void {
    clearTimeout(debounceTimer);

    router.get(
        index.url(),
        { search: search.value.trim() },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

/** Typing searches a beat after the admin stops; Enter does not wait for it. */
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(runSearch, 300);
});

onBeforeUnmount(() => clearTimeout(debounceTimer));

/**
 * What clicking the badge would do — a blocked user's badge reads as a state,
 * so the label it cannot show goes on the button itself.
 */
function blockActionLabel(user: User): string {
    return user.is_blocked
        ? `رفع مسدودی ${user.name}`
        : `مسدود کردن ${user.name}`;
}

/**
 * Block or unblock the user behind the badge.
 *
 * The browser's own dialog is the confirmation step for now — blocking reaches a
 * real person, so it is never one click. A designed modal replaces this later.
 */
function toggleBlock(user: User): void {
    const question = user.is_blocked
        ? `مسدودی ${user.name} برداشته بشه؟`
        : `${user.name} مسدود بشه؟ از این به بعد نمی‌تونه وارد بشه.`;

    if (!window.confirm(question)) {
        return;
    }

    router.patch(
        update.url(user.id),
        { action: 'block', is_blocked: !user.is_blocked },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="کاربران | پنل مدیریت" />

        <div class="mx-auto w-full max-w-5xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                کاربران
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                {{ users.meta.total }} کاربر روی پلتفرم.
            </p>

            <form
                novalidate
                role="search"
                class="mt-6"
                @submit.prevent="runSearch"
            >
                <label for="search" class="sr-only">جست‌وجوی کاربر</label>
                <input
                    id="search"
                    v-model="search"
                    type="search"
                    name="search"
                    class="field-input text-right"
                    placeholder="جست‌وجو با نام یا ایمیل"
                />
            </form>

            <div
                class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
            >
                <div v-if="users.data.length > 0" class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-right text-sm">
                        <thead
                            class="border-b border-slate-200 bg-slate-50 text-[13px] text-slate-500"
                        >
                            <tr>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    نام
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    ایمیل
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    تأیید ایمیل
                                </th>
                                <th scope="col" class="px-5 py-3 font-bold">
                                    وضعیت
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-5 py-4">
                                    <Link
                                        :href="show.url(user.id)"
                                        class="font-bold text-slate-900 underline-offset-4 hover:underline"
                                    >
                                        {{ user.name }}
                                    </Link>
                                </td>
                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-left text-slate-600"
                                >
                                    {{ user.email }}
                                </td>
                                <td class="px-5 py-4">
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
                                </td>
                                <!--
                                    Only the blocked state gets a word. `CONTEXT.md`
                                    names no opposite of Blocked, so a user who is
                                    not blocked shows the action instead of a
                                    status the domain does not have.
                                -->
                                <td class="px-5 py-4">
                                    <button
                                        type="button"
                                        :title="blockActionLabel(user)"
                                        :aria-label="blockActionLabel(user)"
                                        :class="[
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-bold transition-colors focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none',
                                            user.is_blocked
                                                ? 'bg-red-50 text-red-700 hover:bg-red-100'
                                                : 'text-slate-400 hover:bg-slate-100 hover:text-slate-700',
                                        ]"
                                        @click="toggleBlock(user)"
                                    >
                                        {{
                                            user.is_blocked
                                                ? 'مسدود'
                                                : 'مسدود کردن'
                                        }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-14 text-center">
                    <p class="text-base font-bold text-slate-900">
                        کاربری با این مشخصات پیدا نشد
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        جست‌وجو روی نام و ایمیل انجام می‌شه. بخشی از نام یا
                        ایمیل رو امتحان کن.
                    </p>
                </div>
            </div>

            <nav
                v-if="users.meta.last_page > 1"
                aria-label="صفحه‌بندی کاربران"
                class="mt-6 flex items-center justify-between gap-4"
            >
                <PaginationLink :href="users.links.prev">
                    صفحه‌ی قبل
                </PaginationLink>

                <p class="text-sm text-slate-500">
                    صفحه‌ی {{ users.meta.current_page }} از
                    {{ users.meta.last_page }}
                </p>

                <PaginationLink :href="users.links.next">
                    صفحه‌ی بعد
                </PaginationLink>
            </nav>
        </div>
    </main>
</template>
