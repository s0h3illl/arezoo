<script setup lang="ts">
/** PROTOTYPE — throwaway switcher bar. See usePrototypeState.ts. */
import { onMounted, onUnmounted } from 'vue';

import type {
    UnverifiedTreatment,
    VariantKey,
} from '@/components/prototype/usePrototypeState';
import {
    hasAvatar,
    isVerified,
    unread,
    unverified,
    variant,
    variantNames,
} from '@/components/prototype/usePrototypeState';

const isDev = import.meta.env.DEV;

const keys: VariantKey[] = ['A', 'B', 'C'];
const treatments: UnverifiedTreatment[] = ['live', 'disabled', 'hidden'];
const treatmentNames: Record<UnverifiedTreatment, string> = {
    live: 'باز — می‌رود و برمی‌گردد',
    disabled: 'غیرفعال',
    hidden: 'پنهان',
};
const counts = [0, 3, 12];

function cycle(step: number): void {
    const next =
        (keys.indexOf(variant.value) + step + keys.length) % keys.length;

    variant.value = keys[next];
}

function cycleTreatment(): void {
    const next = (treatments.indexOf(unverified.value) + 1) % treatments.length;

    unverified.value = treatments[next];
}

function cycleUnread(): void {
    unread.value = counts[(counts.indexOf(unread.value) + 1) % counts.length];
}

function onKey(event: KeyboardEvent): void {
    const target = event.target as HTMLElement | null;

    if (
        target &&
        (target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.isContentEditable)
    ) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        cycle(1);
    }

    if (event.key === 'ArrowRight') {
        cycle(-1);
    }
}

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <div
        v-if="isDev"
        dir="rtl"
        class="fixed bottom-4 left-1/2 z-[100] flex -translate-x-1/2 flex-wrap items-center justify-center gap-1 rounded-full border border-slate-700 bg-slate-900 px-2 py-1.5 text-white shadow-2xl"
    >
        <button
            type="button"
            class="size-7 rounded-full hover:bg-slate-700"
            @click="cycle(-1)"
        >
            ‹
        </button>

        <span class="px-2 text-[12px] font-bold whitespace-nowrap">
            {{ variant }} — {{ variantNames[variant] }}
        </span>

        <button
            type="button"
            class="size-7 rounded-full hover:bg-slate-700"
            @click="cycle(1)"
        >
            ›
        </button>

        <span class="mx-1 h-5 w-px bg-slate-700"></span>

        <button
            type="button"
            class="rounded-full px-2.5 py-1 text-[11px] hover:bg-slate-700"
            :class="isVerified ? 'text-emerald-300' : 'text-amber-300'"
            @click="isVerified = !isVerified"
        >
            {{ isVerified ? 'تأییدشده' : 'تأییدنشده' }}
        </button>

        <button
            type="button"
            class="rounded-full px-2.5 py-1 text-[11px] text-slate-300 hover:bg-slate-700"
            @click="cycleTreatment"
        >
            بدون تأیید: {{ treatmentNames[unverified] }}
        </button>

        <button
            type="button"
            class="rounded-full px-2.5 py-1 text-[11px] text-slate-300 hover:bg-slate-700"
            @click="cycleUnread"
        >
            نخوانده: {{ unread }}
        </button>

        <button
            type="button"
            class="rounded-full px-2.5 py-1 text-[11px] text-slate-300 hover:bg-slate-700"
            @click="hasAvatar = !hasAvatar"
        >
            آواتار: {{ hasAvatar ? 'دارد' : 'ندارد' }}
        </button>
    </div>
</template>
