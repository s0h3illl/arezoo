<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps<{
    /** Whose profile this is — see `App\Http\Controllers\ProfileController`. */
    user: {
        name: string;
        username: string;
        avatar: string | null;
        bio: string | null;
    };
}>();
</script>

<template>
    <!--
        The header, the wish grid, and everything the owner may do to it are the
        next ticket. This is only enough page for the route to lead somewhere.
    -->
    <main class="flex-1">
        <Head :title="user.name" />

        <div class="mx-auto w-full max-w-5xl px-4 pt-8 pb-12 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-black text-slate-900 sm:text-3xl">
                {{ user.name }}
            </h1>
            <p class="mt-1 text-sm text-slate-500" dir="ltr">
                @{{ user.username }}
            </p>
        </div>
    </main>
</template>
