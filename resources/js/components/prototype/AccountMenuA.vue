<script setup lang="ts">
/** PROTOTYPE A — فهرست ساده. Throwaway; see usePrototypeState.ts. */
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

import UserIcon from '@/components/icons/UserIcon.vue';
import { accountMenuItems } from '@/components/prototype/accountMenuItems';
import {
    isVerified,
    unread,
    unverified,
} from '@/components/prototype/usePrototypeState';
import { logout } from '@/routes';
import type { User } from '@/types';

const props = defineProps<{ user: User }>();

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
            class="relative flex size-[38px] items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition-colors hover:border-emerald-200 hover:bg-emerald-50 focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none data-[state=open]:border-emerald-300 data-[state=open]:bg-emerald-50"
        >
            <UserIcon />
            <span
                v-if="unread > 0"
                aria-hidden="true"
                class="absolute -top-1 -left-1 size-[9px] rounded-full bg-rose-500 ring-2 ring-white"
            ></span>
        </DropdownMenuTrigger>

        <DropdownMenuPortal>
            <DropdownMenuContent
                dir="rtl"
                align="end"
                :side-offset="8"
                class="data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:zoom-in-95 z-50 min-w-[200px] rounded-2xl border border-slate-200 bg-white p-1.5 shadow-[0_16px_40px_-16px_rgb(15_23_42/0.35)]"
            >
                <DropdownMenuItem
                    v-for="item in items"
                    :key="item.label"
                    as-child
                    :disabled="isBlocked(item.needsVerification)"
                >
                    <Link
                        :href="item.href"
                        class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 outline-none data-[disabled]:pointer-events-none data-[disabled]:text-slate-300 data-[highlighted]:bg-emerald-50 data-[highlighted]:text-emerald-700"
                    >
                        {{ item.label }}
                        <span
                            v-if="item.badge && unread > 0"
                            class="min-w-[20px] rounded-full bg-rose-500 px-1.5 py-0.5 text-center text-[11px] font-bold text-white"
                        >
                            {{ unread }}
                        </span>
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator class="my-1.5 h-px bg-slate-100" />

                <!-- The question this variant answers: <Link :href="logout()"> already carries method post. -->
                <DropdownMenuItem as-child>
                    <Link
                        :href="logout()"
                        as="button"
                        type="button"
                        class="flex w-full items-center rounded-xl px-3 py-2.5 text-right text-sm font-medium text-rose-600 outline-none data-[highlighted]:bg-rose-50"
                    >
                        خروج
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
