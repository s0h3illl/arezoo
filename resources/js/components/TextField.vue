<script setup lang="ts">
import { useTemplateRef } from 'vue';

defineProps<{
    id: string;
    /** The form field name — left off where nothing needs to target the input by it. */
    name?: string;
    label: string;
    type?: string;
    dir?: string;
    placeholder?: string;
    autocomplete?: string;
    error?: string;
}>();

const model = defineModel<string>({ required: true });

const input = useTemplateRef<HTMLInputElement>('input');

/**
 * Hand focus to this field.
 *
 * Exposed for the caller that owns the moment focus should move rather than the
 * field itself — the add-wish modal takes the first field when it opens, which
 * is a decision about the dialog, not about any one input.
 */
function focus(): void {
    input.value?.focus();
}

defineExpose({ focus });
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <div class="flex items-center justify-between">
            <label :for="id" class="text-[13px] font-bold text-slate-700">
                {{ label }}
            </label>
            <slot name="label-action" />
        </div>
        <input
            :id="id"
            ref="input"
            v-model="model"
            class="field-input"
            :name="name"
            :type="type ?? 'text'"
            :dir="dir"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${id}-error` : undefined"
        />
        <p v-if="error" :id="`${id}-error`" class="text-xs text-red-600">
            {{ error }}
        </p>
    </div>
</template>
