<script setup lang="ts">
defineProps<{
    id: string;
    label: string;
    type?: string;
    dir?: string;
    placeholder?: string;
    autocomplete?: string;
    error?: string;
}>();

const model = defineModel<string>({ required: true });
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
            v-model="model"
            class="field-input"
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
