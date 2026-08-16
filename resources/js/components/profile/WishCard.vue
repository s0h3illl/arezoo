<script setup lang="ts">
import { ProgressIndicator, ProgressRoot } from 'reka-ui';
import { computed } from 'vue';

import ShareIcon from '@/components/icons/ShareIcon.vue';
import DeleteWishDialog from '@/components/profile/DeleteWishDialog.vue';
import { shareProfile } from '@/composables/useShare';
import { formatNumber, formatShare, formatToman, shareOf } from '@/lib/format';
import type { Wish } from '@/types';

/**
 * Only the fields the card renders, rather than the whole `Wish` it is handed.
 * The narrowing is the card's contract: it says what a caller must supply, and
 * keeps a column added to the table from silently becoming something this
 * component is assumed to show.
 *
 * `deletable` is the page's answer, not the card's guess: the profile is told by
 * the server whose it is, and passes that down.
 */
const props = defineProps<{
    wish: Pick<
        Wish,
        'id' | 'title' | 'description' | 'thumbnail' | 'price' | 'received'
    >;
    deletable: boolean;
}>();

/**
 * The bar clamps at full and the figures below it do not.
 *
 * Contributions are uncapped (ADR-0004), so a wish may hold more than its
 * price. Passing the clamped amount as the value with the price as the maximum
 * gives both the full bar and reka-ui's `data-state="complete"` in one go; the
 * true amount is published beside it, because the bar is a gauge and the
 * figures are the record.
 */
const barValue = computed(() =>
    Math.min(props.wish.received, props.wish.price),
);

/** Already clamped by the value above, so this share never passes full width. */
const barWidth = computed(
    () => `${shareOf(barValue.value, props.wish.price) * 100}%`,
);
</script>

<template>
    <!--
        Inert on purpose. The wish arrives carrying its `purchase_link` and this
        card declines to render it: the link belongs to the wish detail page, and
        a card-wide click target would swallow the delete and share buttons on
        the footer below.
    -->
    <article
        class="flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)]"
    >
        <img
            v-if="wish.thumbnail"
            :src="wish.thumbnail"
            alt=""
            class="aspect-[4/3] w-full object-cover"
        />
        <div
            v-else
            aria-hidden="true"
            class="aspect-[4/3] w-full wish-cover-placeholder"
        ></div>

        <div class="flex flex-1 flex-col p-5">
            <h3 class="text-base font-extrabold text-slate-900">
                {{ wish.title }}
            </h3>

            <!--
                Absent on a wish that was added without one — the column is
                nullable, because only the title and the price are asked for.
            -->
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
                class="mt-auto flex items-center justify-end gap-1 border-t border-slate-100 pt-3"
            >
                <DeleteWishDialog v-if="deletable" :wish="wish" />

                <!--
                    The icon is drawn a size larger than the one beside it, so
                    the button settles the matter for both rather than the
                    marketing page's copy of it being resized to suit a card.
                -->
                <a
                    href="#"
                    data-test="share"
                    aria-label="اشتراک لینک پروفایل"
                    class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-bold text-slate-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600 [&>svg]:size-[18px]"
                    @click.prevent="shareProfile()"
                >
                    <ShareIcon />
                    اشتراک
                </a>
            </div>
        </div>
    </article>
</template>
