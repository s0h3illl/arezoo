<script setup lang="ts">
import AdminHeader from '@/components/admin/AdminHeader.vue';
</script>

<template>
    <div dir="rtl" class="flex min-h-screen flex-col bg-slate-50">
        <AdminHeader />
        <slot />
    </div>
</template>
