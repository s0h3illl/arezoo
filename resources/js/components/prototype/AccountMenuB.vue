<script setup lang="ts">
/** PROTOTYPE B — کارت هویت. Throwaway; see usePrototypeState.ts. */
import { Link } from '@inertiajs/vue3';
import {
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuPortal,
    DropdownMenuRoot,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from 'reka-ui';
import { computed } from 'vue';

import { accountMenuItems } from '@/components/prototype/accountMenuItems';
import {
    fakeAvatar,
    hasAvatar,
    isVerified,
    unread,
    unverified,
} from '@/components/prototype/usePrototypeState';
import { logout } from '@/routes';
import type { User } from '@/types';

const props = defineProps<{ user: User }>();

const glyphs: Record<string, string> = {
    'صفحه من':
        '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    'اطلاعات من':
        '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    پیام‌ها:
        '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    برداشت‌ها:
        '<path d="M3 8a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v2"/><path d="M3 8v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7H7"/>',
};

const items = computed(() =>
    accountMenuItems(props.user.username).filter(
        (item) =>
            isVerified.value ||
            !item.needsVerification ||
            unverified.value !== 'hidden',
    ),
);

function isBlocked(needsVerification: boolean): boolean {
    return (
        needsVerification &&
        !isVerified.value &&
        unverified.value === 'disabled'
    );
}
</script>

<template>
    <DropdownMenuRoot dir="rtl">
        <DropdownMenuTrigger
            aria-label="حساب کاربری"
            class="relative flex size-[38px] items-center justify-center rounded-full ring-2 ring-transparent transition-all hover:ring-emerald-200 focus-visible:ring-emerald-500/60 focus-visible:outline-none data-[state=open]:ring-emerald-300"
        >
            <img
                v-if="hasAvatar"
                :src="fakeAvatar(user.name)"
                alt=""
                class="size-full rounded-full object-cover"
            />
            <span
                v-else
                class="flex size-full items-center justify-center rounded-full bg-emerald-100 text-sm font-black text-emerald-700"
            >
                {{ user.name.charAt(0) }}
            </span>
            <span
                v-if="unread > 0"
                aria-hidden="true"
                class="absolute -top-0.5 -left-0.5 size-[10px] rounded-full bg-rose-500 ring-2 ring-white"
            ></span>
        </DropdownMenuTrigger>

        <DropdownMenuPortal>
            <DropdownMenuContent
                dir="rtl"
                align="end"
                :side-offset="10"
                class="z-50 w-[260px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_16px_40px_-16px_rgb(15_23_42/0.35)]"
            >
                <div class="flex items-center gap-3 bg-slate-50 px-4 py-3.5">
                    <img
                        v-if="hasAvatar"
                        :src="fakeAvatar(user.name)"
                        alt=""
                        class="size-10 shrink-0 rounded-full object-cover"
                    />
                    <span
                        v-else
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-black text-emerald-700"
                    >
                        {{ user.name.charAt(0) }}
                    </span>

                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">
                            {{ user.name }}
                        </p>
                        <p dir="ltr" class="truncate text-xs text-slate-500">
                            @{{ user.username }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="!isVerified && unverified !== 'hidden'"
                    class="border-y border-amber-100 bg-amber-50 px-4 py-2 text-[12px] font-bold text-amber-700"
                >
                    ایمیلت هنوز تأیید نشده
                </p>

                <div class="p-1.5">
                    <DropdownMenuItem
                        v-for="item in items"
                        :key="item.label"
                        as-child
                        :disabled="isBlocked(item.needsVerification)"
                    >
                        <Link
                            :href="item.href"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 outline-none data-[disabled]:pointer-events-none data-[disabled]:text-slate-300 data-[highlighted]:bg-emerald-50 data-[highlighted]:text-emerald-700"
                        >
                            <svg
                                aria-hidden="true"
                                class="size-[18px] shrink-0 text-slate-400"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                v-html="glyphs[item.label]"
                            ></svg>
                            <span class="flex-1">{{ item.label }}</span>
                            <span
                                v-if="item.badge && unread > 0"
                                class="min-w-[20px] rounded-full bg-rose-500 px-1.5 py-0.5 text-center text-[11px] font-bold text-white"
                            >
                                {{ unread }}
                            </span>
                        </Link>
                    </DropdownMenuItem>
                </div>

                <DropdownMenuSeparator class="h-px bg-slate-100" />

                <div class="p-1.5">
                    <DropdownMenuItem as-child>
                        <Link
                            :href="logout()"
                            as="button"
                            type="button"
                            class="flex w-full items-center rounded-xl px-3 py-2.5 text-right text-sm font-bold text-rose-600 outline-none data-[highlighted]:bg-rose-50"
                        >
                            خروج از حساب
                        </Link>
                    </DropdownMenuItem>
                </div>
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
