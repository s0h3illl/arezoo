<script setup lang="ts">
import { ConfigProvider } from 'reka-ui';

import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppToaster from '@/components/AppToaster.vue';
import { provideHeaderNav } from '@/composables/useHeaderNav';

/** Opened here so the header below and the page inside share one list. */
provideHeaderNav();
</script>

<template>
    <ConfigProvider dir="rtl">
        <div dir="rtl" class="flex min-h-screen flex-col bg-slate-50">
            <AppHeader />
            <slot />
            <AppFooter />

            <AppToaster />
        </div>
    </ConfigProvider>
</template>
