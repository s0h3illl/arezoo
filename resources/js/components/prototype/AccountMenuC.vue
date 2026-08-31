<script setup lang="ts">
/** PROTOTYPE C — کشوی کناری. Throwaway; see usePrototypeState.ts. */
import { Link, router } from '@inertiajs/vue3';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui';
import { computed, ref } from 'vue';

import CloseIcon from '@/components/icons/CloseIcon.vue';
import UserIcon from '@/components/icons/UserIcon.vue';
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

const isOpen = ref(false);

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

/**
 * The question this variant answers: a drawer lives in the header, which
 * survives an Inertia visit, so nothing closes it but this.
 */
function signOut(): void {
    isOpen.value = false;
    router.post(logout.url());
}
</script>

<template>
    <DialogRoot v-model:open="isOpen">
        <DialogTrigger
            aria-label="حساب کاربری"
            class="relative flex size-[38px] items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition-colors hover:border-emerald-200 hover:bg-emerald-50 focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none"
        >
            <UserIcon />
            <span
                v-if="unread > 0"
                class="absolute -top-1.5 -left-1.5 min-w-[18px] rounded-full bg-rose-500 px-1 text-center text-[10px] leading-[18px] font-bold text-white ring-2 ring-white"
            >
                {{ unread }}
            </span>
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-[2px]"
            />

            <DialogContent
                dir="rtl"
                class="fixed inset-y-0 left-0 z-50 flex w-[290px] max-w-[85vw] flex-col bg-white shadow-[16px_0_40px_-16px_rgb(15_23_42/0.35)] focus:outline-none"
            >
                <div
                    class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-5"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <img
                            v-if="hasAvatar"
                            :src="fakeAvatar(user.name)"
                            alt=""
                            class="size-11 shrink-0 rounded-full object-cover"
                        />
                        <span
                            v-else
                            class="flex size-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 font-black text-emerald-700"
                        >
                            {{ user.name.charAt(0) }}
                        </span>

                        <div class="min-w-0">
                            <DialogTitle
                                class="truncate text-[15px] font-bold text-slate-900"
                            >
                                {{ user.name }}
                            </DialogTitle>
                            <DialogDescription
                                dir="ltr"
                                class="truncate text-xs text-slate-500"
                            >
                                @{{ user.username }}
                            </DialogDescription>
                        </div>
                    </div>

                    <DialogClose
                        aria-label="بستن"
                        class="mt-1 text-slate-400 transition-colors hover:text-slate-700"
                    >
                        <CloseIcon />
                    </DialogClose>
                </div>

                <p
                    v-if="!isVerified && unverified !== 'hidden'"
                    class="bg-amber-50 px-5 py-2.5 text-[12px] font-bold text-amber-700"
                >
                    ایمیلت هنوز تأیید نشده
                </p>

                <nav class="flex flex-1 flex-col gap-1 p-3">
                    <Link
                        v-for="item in items"
                        :key="item.label"
                        :href="item.href"
                        :class="[
                            'flex items-center justify-between rounded-xl px-4 py-3.5 text-[15px] font-medium transition-colors',
                            isBlocked(item.needsVerification)
                                ? 'pointer-events-none text-slate-300'
                                : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-700',
                        ]"
                        @click="isOpen = false"
                    >
                        {{ item.label }}
                        <span
                            v-if="item.badge && unread > 0"
                            class="min-w-[22px] rounded-full bg-rose-500 px-1.5 py-0.5 text-center text-[11px] font-bold text-white"
                        >
                            {{ unread }}
                        </span>
                    </Link>
                </nav>

                <div class="border-t border-slate-200 p-3">
                    <button
                        type="button"
                        class="w-full rounded-xl bg-rose-50 px-4 py-3 text-[15px] font-bold text-rose-600 transition-colors hover:bg-rose-100"
                        @click="signOut"
                    >
                        خروج از حساب
                    </button>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
