<script setup lang="ts">
/**
 * A single file, chosen from the reader's own machine.
 *
 * The model holds the chosen `File` rather than the input's value, because a
 * file input's value is not something a program is allowed to write back.
 */
defineProps<{
    id: string;
    /** The form field name — left off where nothing needs to target the field by it. */
    name?: string;
    label: string;
    accept?: string;
    hint?: string;
    error?: string;
}>();

const model = defineModel<File | null>({ default: null });

/** Dismissing the picker without choosing anything clears the field. */
function choose(event: Event): void {
    const input = event.target as HTMLInputElement;

    model.value = input.files?.[0] ?? null;
}
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label :for="id" class="text-[13px] font-bold text-slate-700">
            {{ label }}
        </label>
        <input
            :id="id"
            type="file"
            class="field-input py-[9px] text-slate-500 file:me-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-emerald-700"
            :name="name"
            :accept="accept"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${id}-error` : undefined"
            @change="choose"
        />
        <p v-if="error" :id="`${id}-error`" class="text-xs text-red-600">
            {{ error }}
        </p>
        <p v-else-if="hint" class="text-xs text-slate-400">{{ hint }}</p>
    </div>
</template>
