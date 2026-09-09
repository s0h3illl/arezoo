<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AvatarFallback,
    AvatarImage,
    AvatarRoot,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuPortal,
    DropdownMenuRoot,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from 'reka-ui';
import type { Component } from 'vue';
import { computed } from 'vue';

import HomeIcon from '@/components/icons/HomeIcon.vue';
import MailIcon from '@/components/icons/MailIcon.vue';
import UserIcon from '@/components/icons/UserIcon.vue';
import WalletIcon from '@/components/icons/WalletIcon.vue';
import { dashboard, logout, profile } from '@/routes';
import { finance, messages } from '@/routes/dashboard';
import type { User } from '@/types';

const props = defineProps<{ user: User; isVerified: boolean }>();

type AccountMenuRow = {
    label: string;
    href: string;
    icon: Component;
    test: string;
    needsVerification: boolean;
};

const initial = computed(() => [...props.user.name][0] ?? '');

const rows = computed<AccountMenuRow[]>(() => [
    {
        label: 'صفحه من',
        href: profile(props.user.username).url,
        icon: HomeIcon,
        test: 'profile',
        needsVerification: false,
    },
    {
        label: 'اطلاعات من',
        href: dashboard().url,
        icon: UserIcon,
        test: 'account-menu-account',
        needsVerification: false,
    },
    {
        label: 'پیام‌ها',
        href: messages().url,
        icon: MailIcon,
        test: 'account-menu-messages',
        needsVerification: true,
    },
    {
        label: 'مالی',
        href: finance().url,
        icon: WalletIcon,
        test: 'account-menu-finance',
        needsVerification: true,
    },
]);
</script>

<template>
    <DropdownMenuRoot>
        <DropdownMenuTrigger
            aria-label="حساب کاربری"
            data-test="account-menu"
            class="flex size-[38px] items-center justify-center rounded-full ring-2 ring-transparent transition-all hover:ring-emerald-200 focus-visible:ring-emerald-500/60 focus-visible:outline-none data-[state=open]:ring-emerald-300"
        >
            <AvatarRoot
                class="flex size-full items-center justify-center overflow-hidden rounded-full bg-emerald-100"
            >
                <AvatarImage
                    v-if="user.avatar"
                    :src="user.avatar"
                    alt=""
                    class="size-full object-cover"
                />
                <AvatarFallback
                    class="text-sm font-black text-emerald-700"
                    :delay-ms="0"
                >
                    {{ initial }}
                </AvatarFallback>
            </AvatarRoot>
        </DropdownMenuTrigger>

        <DropdownMenuPortal>
            <DropdownMenuContent
                align="end"
                :side-offset="10"
                data-test="account-menu-panel"
                class="z-50 w-[260px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_16px_40px_-16px_rgb(15_23_42/0.35)]"
            >
                <div class="flex items-center gap-3 bg-slate-50 px-4 py-3.5">
                    <AvatarRoot
                        class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-100"
                    >
                        <AvatarImage
                            v-if="user.avatar"
                            :src="user.avatar"
                            alt=""
                            class="size-full object-cover"
                        />
                        <AvatarFallback
                            class="text-sm font-black text-emerald-700"
                            :delay-ms="0"
                        >
                            {{ initial }}
                        </AvatarFallback>
                    </AvatarRoot>

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
                    v-if="!isVerified"
                    data-test="account-menu-unverified"
                    class="border-y border-amber-100 bg-amber-50 px-4 py-2 text-xs font-bold text-amber-700"
                >
                    ایمیلت هنوز تأیید نشده
                </p>

                <div class="p-1.5">
                    <DropdownMenuItem
                        v-for="row in rows"
                        :key="row.label"
                        as-child
                        :disabled="row.needsVerification && !isVerified"
                    >
                        <Link
                            :href="row.href"
                            :data-test="row.test"
                            class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 outline-none data-[disabled]:pointer-events-none data-[disabled]:text-slate-300 data-[highlighted]:bg-emerald-50 data-[highlighted]:text-emerald-700"
                        >
                            <component
                                :is="row.icon"
                                class="size-[18px] shrink-0 text-slate-400 group-data-[disabled]:text-slate-300"
                            />
                            {{ row.label }}
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
                            data-test="account-menu-sign-out"
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
