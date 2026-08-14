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
import { ref, useTemplateRef } from 'vue';

import FileField from '@/components/FileField.vue';
import CloseIcon from '@/components/icons/CloseIcon.vue';
import PlusIcon from '@/components/icons/PlusIcon.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextAreaField from '@/components/TextAreaField.vue';
import TextField from '@/components/TextField.vue';
import { store } from '@/routes/wishes';

const open = ref(false);

const titleField = useTemplateRef<InstanceType<typeof TextField>>('titleField');

const form = useForm({
    title: '',
    description: '',
    purchase_link: '',
    price: '',
    thumbnail: null as File | null,
});

/**
 * Publish the wish, and let the server's answer decide what happens next.
 *
 * Nothing is inserted optimistically: a rejected submission leaves the dialog
 * open with its errors inline and everything typed still there, which Inertia
 * gives for free by preserving component state on a validation error. A
 * successful one closes and empties the dialog, and the new wish is simply in
 * the profile's refreshed props.
 *
 * A picture makes this multipart, which Inertia switches to on its own the
 * moment the data holds a `File`. `forceFormData` is deliberately left off, so
 * a wish without one travels as JSON: Pest's browser plugin serves Laravel a
 * request it has parsed itself, and it parses urlencoded bodies and nothing
 * else — under a forced multipart body every submission would reach the server
 * empty and no browser test could ever watch a wish being added.
 */
function submit(): void {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => setOpen(false),
    });
}

/**
 * Open or close the dialog, emptying it on the way out.
 *
 * Closing throws away what was typed *and* the errors it earned. Without the
 * second part, a dialog dismissed mid-mistake would open again already red.
 */
function setOpen(isOpen: boolean): void {
    open.value = isOpen;

    if (!isOpen) {
        form.reset();
        form.clearErrors();
    }
}

/**
 * Take the first field rather than the close button.
 *
 * The close button is first in the source, so the dialog's own auto-focus would
 * land there and the reader would have to tab into the thing they opened.
 */
function focusTitle(event: Event): void {
    event.preventDefault();

    titleField.value?.focus();
}
</script>

<template>
    <!--
        A reka-ui dialog rather than anything hand-rolled: the focus trap, the
        Escape key, dismissal by clicking the backdrop, `aria-modal` and the lock
        on the page's own scrolling all come with it.
    -->
    <DialogRoot :open="open" @update:open="setOpen">
        <DialogTrigger
            class="flex h-12 shrink-0 items-center gap-2 rounded-xl bg-emerald-500 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-500/40 transition-colors hover:bg-emerald-600"
        >
            <PlusIcon />
            آرزوی جدید
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-[2px]"
            />

            <!--
                The portal mounts this on `<body>`, outside the layout's
                `dir="rtl"` wrapper, so the direction is declared again here or
                every field in the dialog reads left to right.
            -->
            <DialogContent
                dir="rtl"
                class="fixed top-1/2 left-1/2 z-50 max-h-[90dvh] w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-3xl border border-slate-200 bg-white p-6 shadow-[0_24px_64px_-24px_rgb(15_23_42/0.4)] sm:p-8"
                @open-auto-focus="focusTitle"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <DialogTitle
                            class="text-lg font-black text-slate-900 sm:text-xl"
                        >
                            آرزوی جدید
                        </DialogTitle>
                        <DialogDescription class="mt-1 text-sm text-slate-500">
                            فقط عنوان و قیمت لازمه، بقیه‌اش هر وقت خواستی.
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
                    <TextField
                        id="wish-title"
                        ref="titleField"
                        v-model="form.title"
                        name="title"
                        label="عنوان"
                        type="text"
                        placeholder="چه چیزی می‌خوای؟"
                        :error="form.errors.title"
                    />

                    <TextAreaField
                        id="wish-description"
                        v-model="form.description"
                        name="description"
                        label="توضیح"
                        placeholder="اگر حرفی درباره‌اش هست، همین‌جا بنویس."
                        :error="form.errors.description"
                    />

                    <TextField
                        id="wish-purchase-link"
                        v-model="form.purchase_link"
                        name="purchase_link"
                        label="لینک محصول"
                        type="url"
                        dir="ltr"
                        placeholder="https://example.com"
                        :error="form.errors.purchase_link"
                    />

                    <!--
                        A number field, so a phone offers digits and the browser
                        turns away anything that is not one before it is sent.
                    -->
                    <TextField
                        id="wish-price"
                        v-model="form.price"
                        name="price"
                        label="قیمت (تومان)"
                        type="number"
                        dir="ltr"
                        placeholder="500000"
                        :error="form.errors.price"
                    />

                    <FileField
                        id="wish-thumbnail"
                        v-model="form.thumbnail"
                        name="thumbnail"
                        label="تصویر"
                        accept="image/*"
                        hint="اختیاری، تا ۲ مگابایت."
                        :error="form.errors.thumbnail"
                    />

                    <SubmitButton :processing="form.processing">
                        اضافه کن
                    </SubmitButton>
                </form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
