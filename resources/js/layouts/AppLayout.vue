<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppToaster from '@/components/AppToaster.vue';
// PROTOTYPE — ticket .scratch/dashboard/issues/06-the-account-menu-in-the-header.md
import PrototypeSwitcher from '@/components/prototype/PrototypeSwitcher.vue';
import { provideHeaderNav } from '@/composables/useHeaderNav';

/** Opened here so the header below and the page inside share one list. */
provideHeaderNav();
</script>

<template>
    <div dir="rtl" class="flex min-h-screen flex-col bg-slate-50">
        <AppHeader />
        <slot />
        <AppFooter />

        <AppToaster />
        <PrototypeSwitcher />
    </div>
</template>
