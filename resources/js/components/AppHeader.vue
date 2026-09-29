<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AccountMenu from '@/components/AccountMenu.vue';
import HomeIcon from '@/components/icons/HomeIcon.vue';
import { useHeaderNav } from '@/composables/useHeaderNav';
import { home, login } from '@/routes';

const page = usePage();

/** Whatever the current page provided, empty until it does. */
const navItems = useHeaderNav();

const user = computed(() => page.props.auth.user);

const isVerified = computed(() => page.props.auth.isVerified);

/**
 * A page's own links are section anchors, which Inertia's <Link> would try to
 * visit. Those render as plain anchors.
 */
function isAnchor(href: string): boolean {
    return href.startsWith('#');
}
</script>

<template>
    <header class="flex items-center justify-between px-5 py-4 sm:px-8">
        <Link
            :href="home()"
            class="flex items-center gap-2 text-2xl font-extrabold text-emerald-600"
        >
            <img src="/logo.svg" alt="" class="size-9" />
            آرزو
        </Link>

        <div class="flex items-center gap-2 sm:gap-5">
            <nav
                v-if="navItems.length"
                id="page-nav"
                aria-label="بخش‌های صفحه"
                class="flex items-center gap-2 sm:gap-5"
            >
                <component
                    :is="isAnchor(item.href) ? 'a' : Link"
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    class="flex h-11 items-center px-1 text-sm font-medium text-slate-600 transition-colors hover:text-emerald-700"
                >
                    {{ item.label }}
                </component>
            </nav>

            <Link
                v-else
                :href="home()"
                aria-label="صفحه اصلی"
                class="flex size-[38px] items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition-colors hover:border-emerald-200 hover:bg-emerald-50 focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none"
            >
                <HomeIcon />
            </Link>

            <AccountMenu v-if="user" :user="user" :is-verified="isVerified" />

            <Link
                v-else
                :href="login()"
                class="flex h-11 items-center rounded-xl bg-emerald-500 px-5 text-sm font-bold text-white transition-colors hover:bg-emerald-600"
            >
                ورود
            </Link>
        </div>
    </header>
</template>
