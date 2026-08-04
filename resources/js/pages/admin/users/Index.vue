<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';

import PaginationLink from '@/components/admin/PaginationLink.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { index } from '@/routes/admin/users';
import type { Paginated } from '@/types';

defineOptions({ layout: AdminLayout });

type UserRow = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    is_blocked: boolean;
};

const props = defineProps<{
    users: Paginated<UserRow>;
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

/** Persian digits, so the table does not read half in one script and half in another. */
function toPersianDigits(value: number): string {
    return value.toLocaleString('fa-IR');
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
                {{ toPersianDigits(users.total) }} کاربر روی پلتفرم.
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
                                <td class="px-5 py-4 font-bold text-slate-900">
                                    {{ user.name }}
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
                                    names no opposite of Blocked, and inventing one
                                    would harden a status the domain does not have.
                                -->
                                <td class="px-5 py-4">
                                    <span
                                        v-if="user.is_blocked"
                                        class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700"
                                    >
                                        مسدود
                                    </span>
                                    <span v-else class="text-slate-300">—</span>
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
                v-if="users.last_page > 1"
                aria-label="صفحه‌بندی کاربران"
                class="mt-6 flex items-center justify-between gap-4"
            >
                <PaginationLink :href="users.prev_page_url">
                    صفحه‌ی قبل
                </PaginationLink>

                <p class="text-sm text-slate-500">
                    صفحه‌ی {{ toPersianDigits(users.current_page) }} از
                    {{ toPersianDigits(users.last_page) }}
                </p>

                <PaginationLink :href="users.next_page_url">
                    صفحه‌ی بعد
                </PaginationLink>
            </nav>
        </div>
    </main>
</template>
