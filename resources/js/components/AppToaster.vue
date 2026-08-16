<script setup lang="ts">
import {
    ToastClose,
    ToastDescription,
    ToastProvider,
    ToastRoot,
    ToastViewport,
} from 'reka-ui';

import CloseIcon from '@/components/icons/CloseIcon.vue';
import { dismissToast, useToasts } from '@/composables/useToast';

const toasts = useToasts();

function forgetOnClose(id: number, open: boolean): void {
    if (!open) {
        dismissToast(id);
    }
}
</script>

<template>
    <!--
        Swiping down rather than sideways. The default is a swipe to the right,
        which on a page written right to left throws the toast towards the middle
        of the screen instead of off the edge it came from; down reads the same
        whichever way the text runs.
    -->
    <ToastProvider swipe-direction="down" label="اعلان">
        <ToastRoot
            v-for="toast in toasts"
            :key="toast.id"
            data-test="toast"
            class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white py-3 pr-4 pl-3 shadow-[0_16px_40px_-16px_rgb(15_23_42/0.35)] data-[swipe=cancel]:translate-y-0 data-[swipe=cancel]:transition-transform data-[swipe=move]:translate-y-[var(--reka-toast-swipe-move-y)]"
            @update:open="forgetOnClose(toast.id, $event)"
        >
            <!--
                Colour is the only thing that separates the two tones, so it is
                not the only thing: the dot is decoration and the message itself
                says which of them this is.
            -->
            <span
                aria-hidden="true"
                class="size-2 shrink-0 rounded-full"
                :class="
                    toast.tone === 'error' ? 'bg-rose-500' : 'bg-emerald-500'
                "
            ></span>

            <ToastDescription
                class="flex-1 text-sm font-bold text-slate-900"
                :class="toast.tone === 'error' && 'text-rose-600'"
            >
                {{ toast.message }}
            </ToastDescription>

            <ToastClose
                data-test="toast-dismiss"
                aria-label="بستن"
                class="flex size-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
            >
                <CloseIcon />
            </ToastClose>
        </ToastRoot>

        <!--
            Bottom left on a wide screen, which is where bottom right sits once
            the page is mirrored; across the bottom on a narrow one, where there
            is no corner to spare.
        -->
        <ToastViewport
            label="اعلان‌ها ({hotkey})"
            class="fixed bottom-4 left-1/2 z-[60] flex w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 flex-col gap-2 sm:left-6 sm:translate-x-0"
        />
    </ToastProvider>
</template>
