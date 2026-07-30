<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import HomeIcon from '@/components/icons/HomeIcon.vue';
import { home } from '@/routes';
</script>

<template>
    <header class="flex items-center justify-between px-5 py-4 sm:px-8">
        <Link :href="home()" class="text-2xl font-extrabold text-emerald-600">
            آرزو
        </Link>
        <Link
            :href="home()"
            aria-label="صفحه اصلی"
            class="flex size-[38px] items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition-colors hover:border-emerald-200 hover:bg-emerald-50"
        >
            <HomeIcon />
        </Link>
    </header>
</template>
