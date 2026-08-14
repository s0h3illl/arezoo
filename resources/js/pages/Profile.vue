<script setup lang="ts">
import { Head, InfiniteScroll } from '@inertiajs/vue3';
import { AvatarFallback, AvatarImage, AvatarRoot } from 'reka-ui';
import { computed } from 'vue';

import AddWishDialog from '@/components/profile/AddWishDialog.vue';
import WishCard from '@/components/profile/WishCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatNumber } from '@/lib/format';
import type { Paginated, User, Wish } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    /**
     * Whose profile this is. The controller hands over `$user->only([...])`
     * rather than the whole user, so this narrows the shape it narrows —
     * anything wider would publish a stranger's email to every visitor.
     */
    user: Pick<User, 'name' | 'username' | 'avatar' | 'bio'>;
    /** Decided from the session, never from anything the browser can set. */
    is_owner: boolean;
    /** One page of the grid at a time; `<InfiniteScroll>` asks for the rest. */
    wishes: Paginated<Wish>;
}>();

/**
 * The letter that stands in for a picture nobody has uploaded yet.
 *
 * Spread rather than indexed, because a name can open on a letter that is two
 * code units long and `[0]` would cut it in half.
 */
const initial = computed(() => [...props.user.name][0] ?? '');
</script>

<template>
    <main class="flex-1">
        <Head :title="user.name" />

        <div class="mx-auto w-full max-w-7xl px-4 pt-8 pb-12 sm:px-6 lg:px-8">
            <header
                class="flex flex-col items-center gap-5 rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-[0_12px_32px_-16px_rgb(15_23_42/0.12)] sm:flex-row sm:p-8 sm:text-right"
            >
                <!--
                    The fallback renders whenever the picture has not loaded,
                    which covers both a user who has none and one whose file has
                    gone missing. Neither leaves a broken image behind.
                -->
                <AvatarRoot
                    class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-50 sm:size-24"
                >
                    <AvatarImage
                        v-if="user.avatar"
                        :src="user.avatar"
                        alt=""
                        class="size-full object-cover"
                    />
                    <AvatarFallback
                        class="text-2xl font-black text-emerald-600 sm:text-3xl"
                    >
                        {{ initial }}
                    </AvatarFallback>
                </AvatarRoot>

                <div class="flex-1">
                    <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                        {{ user.name }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500" dir="ltr">
                        @{{ user.username }}
                    </p>
                    <p
                        v-if="user.bio"
                        class="mt-3 text-sm leading-loose text-slate-600"
                    >
                        {{ user.bio }}
                    </p>

                    <!--
                        The one stat on the page. It reads the same paginator the
                        grid does, so the two can never disagree.

                        There is deliberately no total-raised chip beside it. A
                        card's own figure answers the question the page is opened
                        to ask — how close is this to being paid for — while a sum
                        across every card answers a different one, and reads as
                        the owner's Balance, which it is not.
                    -->
                    <p
                        class="mt-4 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-xs font-bold text-emerald-700"
                    >
                        <span
                            aria-hidden="true"
                            class="size-2 rounded-full bg-emerald-500"
                        ></span>
                        {{ formatNumber(wishes.meta.total) }} آرزو
                    </p>
                </div>

                <!--
                    Both the button and the dialog behind it: the trigger has to
                    be the thing that opens it for focus to come back here when
                    it closes.
                -->
                <AddWishDialog v-if="is_owner" />
            </header>

            <!--
                Two different screens, not one with the button hidden: an owner
                is invited to start, a visitor is simply told there is nothing.
            -->
            <div
                v-if="wishes.data.length === 0"
                class="mt-6 rounded-3xl border border-dashed border-slate-300 px-6 py-14 text-center"
            >
                <p class="text-base font-bold text-slate-900">
                    {{
                        is_owner
                            ? 'هنوز آرزویی اضافه نکردی'
                            : 'هنوز آرزویی اینجا نیست'
                    }}
                </p>
                <p class="mt-2 text-sm text-slate-500">
                    {{
                        is_owner
                            ? 'اولین آرزوت رو اضافه کن تا این صفحه آماده‌ی فرستادن باشه.'
                            : 'هر وقت آرزویی اضافه بشه، همین‌جا می‌بینیش.'
                    }}
                </p>
            </div>

            <!--
                `items-element` points at the grid itself, because the component
                puts its own scroll triggers around this slot and they must not
                become cards in the grid.
            -->
            <InfiniteScroll v-else data="wishes" items-element="#wish-grid">
                <div
                    id="wish-grid"
                    class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <WishCard
                        v-for="wish in wishes.data"
                        :key="wish.id"
                        :wish="wish"
                    />
                </div>
            </InfiniteScroll>
        </div>
    </main>
</template>
