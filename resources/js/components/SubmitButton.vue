<script setup lang="ts">
defineProps<{
    processing: boolean;
}>();
</script>

<template>
    <button
        type="submit"
        :disabled="processing"
        class="flex h-12 w-full items-center justify-center rounded-[14px] bg-emerald-500 text-base font-extrabold text-white shadow-[0_10px_24px_-8px_rgb(16_185_129/0.5)] transition-colors hover:bg-emerald-600 disabled:opacity-60"
    >
        <svg
            v-if="processing"
            aria-hidden="true"
            class="size-5 animate-spin"
            viewBox="0 0 24 24"
            fill="none"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"
            />
        </svg>
        <span v-else><slot /></span>
    </button>
</template>
