<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui';
import { ref } from 'vue';

import CloseIcon from '@/components/icons/CloseIcon.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextAreaField from '@/components/TextAreaField.vue';
import { update } from '@/routes/admin/withdrawals/note';
import type { Withdrawal } from '@/types/admin';

const props = defineProps<{
    withdrawal: Pick<Withdrawal, 'id' | 'note' | 'owner'>;
}>();

const open = ref(false);

const form = useForm({
    note: props.withdrawal.note,
});

function submit(): void {
    form.put(update.url(props.withdrawal.id), {
        preserveScroll: true,
        onSuccess: () => setOpen(false),
    });
}

function setOpen(isOpen: boolean): void {
    open.value = isOpen;

    if (isOpen) {
        form.note = props.withdrawal.note;
        form.clearErrors();
    }
}
</script>

<template>
    <DialogRoot :open="open" @update:open="setOpen">
        <DialogTrigger
            data-test="note"
            :aria-label="`یادداشت برای ${withdrawal.owner.name}`"
            class="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-200 focus-visible:ring-2 focus-visible:ring-slate-500/40 focus-visible:outline-none"
        >
            یادداشت
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-[2px]"
            />

            <DialogContent
                dir="rtl"
                class="fixed top-1/2 left-1/2 z-50 max-h-[90dvh] w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_24px_64px_-24px_rgb(15_23_42/0.4)] sm:p-8"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <DialogTitle
                            class="text-lg font-black text-slate-900 sm:text-xl"
                        >
                            یادداشت برای {{ withdrawal.owner.name }}
                        </DialogTitle>
                        <DialogDescription class="mt-1 text-sm text-slate-500">
                            هرچی این‌جا بنویسی، توی صفحه‌ی مالی کنار همین
                            درخواست می‌بیندش.
                        </DialogDescription>
                    </div>

                    <DialogClose
                        aria-label="بستن"
                        class="flex size-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                    >
                        <CloseIcon />
                    </DialogClose>
                </div>

                <form
                    novalidate
                    class="mt-6 flex flex-col gap-[18px]"
                    @submit.prevent="submit"
                >
                    <TextAreaField
                        :id="`withdrawal-${withdrawal.id}-note`"
                        v-model="form.note"
                        name="note"
                        label="یادداشت"
                        placeholder="مثلاً چرا این درخواست رد شد و مالک باید چه‌کار کنه."
                        :error="form.errors.note"
                    />

                    <SubmitButton
                        data-test="save-note"
                        :processing="form.processing"
                    >
                        ذخیره کن
                    </SubmitButton>
                </form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
