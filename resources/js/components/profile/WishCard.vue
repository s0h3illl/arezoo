<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ProgressIndicator, ProgressRoot } from 'reka-ui';
import { computed } from 'vue';

import ShareIcon from '@/components/icons/ShareIcon.vue';
import DeleteWishDialog from '@/components/profile/DeleteWishDialog.vue';
import EditWishDialog from '@/components/profile/EditWishDialog.vue';
import { shareLink } from '@/composables/useShare';
import { formatNumber, formatShare, formatToman, shareOf } from '@/lib/format';
import { show } from '@/routes/wishes';
import type { Wish } from '@/types';

/**
 * What the card draws, and what its edit dialog changes — which is why
 * `purchase_link` is here and still never rendered. The narrowing is the card's
 * contract: it says what a caller must supply, and keeps a column added to the
 * table from silently becoming something this component is assumed to handle.
 */
const props = defineProps<{
    wish: Pick<
        Wish,
        | 'id'
        | 'user_id'
        | 'title'
        | 'description'
        | 'thumbnail'
        | 'purchase_link'
        | 'price'
        | 'received'
    >;
}>();

const page = usePage();

const isOwn = computed(() => page.props.auth.user?.id === props.wish.user_id);

const barValue = computed(() =>
    Math.min(props.wish.received, props.wish.price),
);

/** Already clamped by the value above, so this share never passes full width. */
const barWidth = computed(
    () => `${shareOf(barValue.value, props.wish.price) * 100}%`,
);
</script>

<template>
    <article
        class="flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
    >
        <Link :href="show(wish.id)" tabindex="-1" aria-hidden="true">
            <img
                v-if="wish.thumbnail"
                :src="wish.thumbnail"
                alt=""
                class="aspect-[4/3] w-full object-cover"
            />
            <div
                v-else
                class="aspect-[4/3] w-full wish-cover-placeholder"
            ></div>
        </Link>

        <div class="flex flex-1 flex-col p-5">
            <h3 class="text-base font-extrabold text-slate-900">
                <Link
                    :href="show(wish.id)"
                    class="transition-colors hover:text-emerald-600 focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none"
                >
                    {{ wish.title }}
                </Link>
            </h3>

            <p
                v-if="wish.description"
                class="mt-2 line-clamp-2 text-sm leading-loose text-slate-500"
            >
                {{ wish.description }}
            </p>

            <p class="mt-4 text-sm font-bold text-slate-900">
                {{ formatToman(wish.price) }}
            </p>

            <ProgressRoot
                :model-value="barValue"
                :max="wish.price"
                class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100"
            >
                <ProgressIndicator
                    class="h-full rounded-full bg-emerald-500 transition-[width] duration-500"
                    :style="{ width: barWidth }"
                />
            </ProgressRoot>

            <div
                class="mt-2 flex items-center justify-between gap-3 text-xs font-bold"
            >
                <p class="text-slate-500">
                    {{ formatNumber(wish.received) }} از
                    {{ formatNumber(wish.price) }}
                </p>
                <p class="text-emerald-600">
                    {{ formatShare(wish.received, wish.price) }}
                </p>
            </div>

            <div
                class="mt-auto flex items-center gap-1 border-t border-slate-100 pt-3"
            >
                <EditWishDialog v-if="isOwn" :wish="wish" />

                <a
                    href="#"
                    data-test="share"
                    aria-label="اشتراک لینک آرزو"
                    class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-bold text-slate-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600 [&>svg]:size-[18px]"
                    @click.prevent="shareLink(show(wish.id).url)"
                >
                    <ShareIcon />
                    اشتراک
                </a>

                <div v-if="isOwn" class="ms-auto">
                    <DeleteWishDialog :wish="wish" />
                </div>
            </div>
        </div>
    </article>
</template>
