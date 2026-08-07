<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        accent?: 'neutral' | 'emerald';
    }>(),
    {
        accent: 'neutral',
    },
);
</script>

<template>
    <div
        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
    >
        <dt class="text-sm font-bold text-slate-500">{{ label }}</dt>
        <dd
            :class="[
                'mt-2 text-3xl font-black',
                accent === 'emerald' ? 'text-emerald-600' : 'text-slate-900',
            ]"
        >
            <slot />
        </dd>
    </div>
</template>
