<script setup lang="ts">
import { Head, InfiniteScroll } from '@inertiajs/vue3';

import MessageRow from '@/components/dashboard/MessageRow.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Message, Paginated } from '@/types';

defineOptions({ layout: AppLayout });

defineProps<{
    messages: Paginated<Message>;
}>();
</script>

<template>
    <main class="flex-1 px-5 py-8 sm:px-8 sm:py-12">
        <Head title="پیام‌ها" />

        <div class="mx-auto w-full max-w-2xl">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                پیام‌ها
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                هرچی آدم‌ها موقع کمک به آرزوهات نوشتن، این‌جاست.
            </p>

            <p
                v-if="messages.data.length === 0"
                data-test="no-messages"
                class="mt-10 text-center text-sm text-slate-400"
            >
                هنوز پیامی نداری
            </p>

            <InfiniteScroll
                v-else
                data="messages"
                items-element="#message-list"
            >
                <ul id="message-list" class="mt-6 flex flex-col gap-4">
                    <MessageRow
                        v-for="row in messages.data"
                        :key="row.id"
                        :row="row"
                    />
                </ul>
            </InfiniteScroll>
        </div>
    </main>
</template>
