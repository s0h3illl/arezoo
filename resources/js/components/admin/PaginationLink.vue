<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

/**
 * One step of a paginator. A step with nowhere to go stays in the layout as
 * inert text rather than disappearing, so the controls do not shift under the
 * pointer on the first and last pages.
 */
const props = defineProps<{
    href: string | null;
}>();
</script>

<template>
    <component
        :is="props.href ? Link : 'span'"
        :href="props.href ?? undefined"
        :aria-disabled="props.href ? undefined : 'true'"
        :class="[
            'rounded-[14px] border px-4 py-2.5 text-sm font-bold transition-colors',
            props.href
                ? 'border-slate-200 text-slate-700 hover:border-emerald-200 hover:bg-emerald-50 focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none'
                : 'border-slate-100 text-slate-300',
        ]"
    >
        <slot />
    </component>
</template>
