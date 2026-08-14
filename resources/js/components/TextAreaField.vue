<script setup lang="ts">
/**
 * The same field as `TextField`, for writing that runs to more than a line.
 *
 * A sibling rather than a `multiline` switch on `TextField`, because half of
 * that component's props — `type`, `autocomplete` — say nothing to a textarea,
 * and a field that quietly ignores what it is given is worse than two files.
 */
defineProps<{
    id: string;
    /** The form field name — left off where nothing needs to target the field by it. */
    name?: string;
    label: string;
    placeholder?: string;
    error?: string;
}>();

const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label :for="id" class="text-[13px] font-bold text-slate-700">
            {{ label }}
        </label>
        <textarea
            :id="id"
            v-model="model"
            class="field-input resize-y leading-loose"
            :name="name"
            rows="3"
            :placeholder="placeholder"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${id}-error` : undefined"
        ></textarea>
        <p v-if="error" :id="`${id}-error`" class="text-xs text-red-600">
            {{ error }}
        </p>
    </div>
</template>
