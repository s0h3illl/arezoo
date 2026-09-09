<script setup lang="ts">
import { AvatarFallback, AvatarImage, AvatarRoot } from 'reka-ui';
import { computed } from 'vue';

import { formatToman } from '@/lib/format';
import type { Contribution } from '@/types';

const props = defineProps<{
    row: Pick<Contribution, 'state' | 'contributor' | 'amount' | 'settled_at'>;
}>();

const isVisible = computed(() => props.row.state === 'visible');

const placeholder = computed(() => (props.row.state === 'deleted' ? '—' : '؟'));

/** Spread rather than indexed, so a two-code-unit letter is not cut in half. */
const initial = computed(() => [...props.row.contributor.name][0] ?? '');
</script>

<template>
    <li class="flex items-center gap-3 border-t border-slate-100 py-3">
        <AvatarRoot
            v-if="isVisible"
            class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-50"
        >
            <AvatarImage
                v-if="row.contributor.avatar"
                :src="row.contributor.avatar"
                alt=""
                class="size-full object-cover"
            />
            <AvatarFallback class="text-sm font-black text-emerald-600">
                {{ initial }}
            </AvatarFallback>
        </AvatarRoot>

        <span
            v-else
            aria-hidden="true"
            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-black text-slate-400"
        >
            {{ placeholder }}
        </span>

        <div class="min-w-0 flex-1">
            <p
                class="truncate text-sm font-extrabold"
                :class="isVisible ? 'text-slate-900' : 'text-slate-400'"
            >
                {{ row.contributor.name }}
            </p>
            <p v-if="row.settled_at" class="text-xs text-slate-400">
                {{ row.settled_at }}
            </p>
        </div>

        <p class="shrink-0 text-[13.5px] font-black text-emerald-700">
            {{ formatToman(row.amount) }}
        </p>
    </li>
</template>
