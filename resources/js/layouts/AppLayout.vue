<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import { provideHeaderNav } from '@/composables/useHeaderNav';

/** Opened here so the header below and the page inside share one list. */
provideHeaderNav();
</script>

<template>
    <div dir="rtl" class="flex min-h-screen flex-col bg-slate-50">
        <AppHeader />
        <slot />
    </div>
</template>
