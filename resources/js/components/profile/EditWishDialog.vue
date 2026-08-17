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
import { ref, useTemplateRef, watch } from 'vue';

import FileField from '@/components/FileField.vue';
import CloseIcon from '@/components/icons/CloseIcon.vue';
import PencilIcon from '@/components/icons/PencilIcon.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextAreaField from '@/components/TextAreaField.vue';
import TextField from '@/components/TextField.vue';
import { update } from '@/routes/wishes';
import type { Wish } from '@/types';

const props = defineProps<{
    wish: Pick<
        Wish,
        'id' | 'title' | 'description' | 'thumbnail' | 'purchase_link' | 'price'
    >;
}>();

const open = ref(false);

const titleField = useTemplateRef<InstanceType<typeof TextField>>('titleField');

const form = useForm({
    title: props.wish.title,
    description: props.wish.description ?? '',
    purchase_link: props.wish.purchase_link ?? '',
    price: String(props.wish.price),
    thumbnail: null as File | null,
    remove_thumbnail: false,
});

const currentThumbnail = ref(props.wish.thumbnail);

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => setOpen(false),
    };

    if (form.thumbnail instanceof File) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            update.url(props.wish.id),
            options,
        );

        return;
    }

    form.put(update.url(props.wish.id), options);
}

watch(
    () => form.thumbnail,
    (file) => {
        if (file !== null) {
            form.remove_thumbnail = false;
        }
    },
);

function removeThumbnail(): void {
    currentThumbnail.value = null;
    form.thumbnail = null;
    form.remove_thumbnail = true;
}

function setOpen(isOpen: boolean): void {
    open.value = isOpen;

    if (!isOpen) {
        form.reset();
        form.clearErrors();
        currentThumbnail.value = props.wish.thumbnail;
    }
}

function focusTitle(event: Event): void {
    event.preventDefault();

    titleField.value?.focus();
}
</script>

<template>
    <DialogRoot :open="open" @update:open="setOpen">
        <DialogTrigger
            data-test="edit"
            :aria-label="`ویرایش ${wish.title}`"
            class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-bold text-slate-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600"
        >
            <PencilIcon />
            ویرایش
        </DialogTrigger>

        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-[2px]"
            />

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
                            ویرایش آرزو
                        </DialogTitle>
                        <DialogDescription class="mt-1 text-sm text-slate-500">
                            هر چیزی رو می‌تونی عوض کنی. پولی که جمع شده دست
                            نمی‌خوره.
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
                        :id="`wish-${wish.id}-title`"
                        ref="titleField"
                        v-model="form.title"
                        name="title"
                        label="عنوان"
                        type="text"
                        placeholder="چه چیزی می‌خوای؟"
                        :error="form.errors.title"
                    />

                    <TextAreaField
                        :id="`wish-${wish.id}-description`"
                        v-model="form.description"
                        name="description"
                        label="توضیح"
                        placeholder="اگر حرفی درباره‌اش هست، همین‌جا بنویس."
                        :error="form.errors.description"
                    />

                    <TextField
                        :id="`wish-${wish.id}-purchase-link`"
                        v-model="form.purchase_link"
                        name="purchase_link"
                        label="لینک محصول"
                        type="url"
                        dir="ltr"
                        placeholder="https://example.com"
                        :error="form.errors.purchase_link"
                    />

                    <TextField
                        :id="`wish-${wish.id}-price`"
                        v-model="form.price"
                        name="price"
                        label="قیمت (تومان)"
                        type="number"
                        dir="ltr"
                        placeholder="500000"
                        :error="form.errors.price"
                    />

                    <div v-if="currentThumbnail" class="flex flex-col gap-1.5">
                        <p class="text-[13px] font-bold text-slate-700">
                            تصویر فعلی
                        </p>
                        <div class="flex items-center gap-3">
                            <img
                                :src="currentThumbnail"
                                alt=""
                                class="size-16 shrink-0 rounded-xl object-cover"
                            />
                            <button
                                type="button"
                                data-test="remove-thumbnail"
                                class="rounded-lg px-2 py-1.5 text-xs font-bold text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                                @click="removeThumbnail"
                            >
                                حذف تصویر
                            </button>
                        </div>
                    </div>

                    <FileField
                        :id="`wish-${wish.id}-thumbnail`"
                        v-model="form.thumbnail"
                        name="thumbnail"
                        label="تصویر"
                        accept="image/*"
                        :hint="
                            currentThumbnail
                                ? 'اگر فایلی انتخاب کنی، جای تصویر فعلی می‌شینه.'
                                : 'اختیاری، تا ۲ مگابایت.'
                        "
                        :error="form.errors.thumbnail"
                    />

                    <SubmitButton data-test="save" :processing="form.processing">
                        ذخیره کن
                    </SubmitButton>
                </form>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
