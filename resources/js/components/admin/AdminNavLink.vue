<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

/**
 * Sections that have no route yet point at '#', which Inertia's <Link> would
 * try to visit. Those render as plain anchors until their route lands.
 */
const isPlaceholder = computed(() => props.href === '#');
</script>

<template>
    <component
        :is="isPlaceholder ? 'a' : Link"
        :href="href"
        :aria-current="active ? 'page' : undefined"
        class="rounded-xl px-3 py-2 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none"
        :class="
            active
                ? 'bg-emerald-50 font-bold text-emerald-700'
                : 'font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900'
        "
    >
        <slot />
    </component>
</template>
