<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
</script>

<template>
    <div dir="rtl" class="flex min-h-screen flex-col bg-slate-50">
        <AppHeader />
        <slot />
    </div>
</template>
