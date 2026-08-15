<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogOverlay,
    AlertDialogPortal,
    AlertDialogRoot,
    AlertDialogTitle,
    AlertDialogTrigger,
} from 'reka-ui';

import TrashIcon from '@/components/icons/TrashIcon.vue';
import { destroy } from '@/routes/wishes';
import type { Wish } from '@/types';

/**
 * Only what it takes to name the wish being removed and to address the request.
 * The title is here so the question names the thing rather than asking about
 * "this wish", which on a grid of them is not a question anyone can answer.
 */
const props = defineProps<{
    wish: Pick<Wish, 'id' | 'title'>;
}>();

/**
 * Delete the wish, and let the server's answer decide what the page shows.
 *
 * Nothing is removed optimistically: the controller redirects back to the
 * profile, so the grid and the count beside the owner's name are re-read
 * together and cannot drift apart from each other or from the database. The
 * scroll position is kept, because the answer is the same page again and a jump
 * to the top would lose the reader's place in a long grid.
 *
 * Closing is left to `AlertDialogAction`, which does it on click of its own
 * accord — the dialog is reka-ui's to manage, and racing it here would only
 * mean two things deciding when it goes.
 */
function submit(): void {
    router.delete(destroy.url(props.wish.id), { preserveScroll: true });
}
</script>

<template>
    <!--
        An alert dialog rather than a plain one: deleting is destructive, so it
        gets `role="alertdialog"`, focus parked on the cancel button, and no
        dismissal by clicking the backdrop — all of which reka-ui brings.
    -->
    <AlertDialogRoot>
        <!--
            `data-test` rather than the label text, so a test can find this
            button whatever it says — including if it ever loses its word and
            becomes the icon alone. `aria-label` stays for screen readers, and
            names which wish; the two hooks answer different questions.
        -->
        <AlertDialogTrigger
            data-test="delete"
            :aria-label="`حذف ${wish.title}`"
            class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-bold text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
        >
            <TrashIcon />
            حذف
        </AlertDialogTrigger>

        <AlertDialogPortal>
            <AlertDialogOverlay
                class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-[2px]"
            />

            <!--
                The portal mounts this on `<body>`, outside the layout's
                `dir="rtl"` wrapper, so the direction is declared again here.
            -->
            <AlertDialogContent
                dir="rtl"
                class="fixed top-1/2 left-1/2 z-50 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_24px_64px_-24px_rgb(15_23_42/0.4)] sm:p-8"
            >
                <AlertDialogTitle class="text-lg font-black text-slate-900">
                    «{{ wish.title }}» حذف بشه؟
                </AlertDialogTitle>

                <AlertDialogDescription
                    class="mt-2 text-sm leading-loose text-slate-500"
                >
                    این آرزو از پروفایلت برداشته می‌شه و دیگه کسی نمی‌بیندش.
                    پولی که تا حالا برای این آرزو جمع شده سر جاش می‌مونه.
                </AlertDialogDescription>

                <div class="mt-6 flex gap-3">
                    <AlertDialogAction
                        data-test="delete-confirm"
                        class="flex h-11 flex-1 items-center justify-center rounded-[14px] bg-rose-500 text-sm font-extrabold text-white transition-colors hover:bg-rose-600"
                        @click="submit"
                    >
                        حذفش کن
                    </AlertDialogAction>

                    <AlertDialogCancel
                        data-test="delete-cancel"
                        class="flex h-11 flex-1 items-center justify-center rounded-[14px] border border-slate-200 text-sm font-extrabold text-slate-600 transition-colors hover:bg-slate-50"
                    >
                        بی‌خیال
                    </AlertDialogCancel>
                </div>
            </AlertDialogContent>
        </AlertDialogPortal>
    </AlertDialogRoot>
</template>
