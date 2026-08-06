<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AdminNavLink from '@/components/admin/AdminNavLink.vue';
import CloseIcon from '@/components/icons/CloseIcon.vue';
import HomeIcon from '@/components/icons/HomeIcon.vue';
import MenuIcon from '@/components/icons/MenuIcon.vue';
import { home } from '@/routes';
import { dashboard } from '@/routes/admin';
import contributions from '@/routes/admin/contributions';
import users from '@/routes/admin/users';

const page = usePage();

const isMenuOpen = ref(false);

/**
 * Sections beyond these are not built yet, so they hold '#' until each one
 * gets a route.
 */
const navItems = computed(() => [
    { label: 'داشبورد', href: dashboard().url },
    { label: 'کاربران', href: users.index().url },
    { label: 'مشارکت‌ها', href: contributions.index().url },
    { label: 'برداشت‌ها', href: '#' },
    { label: 'تنظیمات', href: '#' },
]);

/**
 * Compared on path alone: the users screen carries its search term in the query
 * string, and a link that unhighlighted itself the moment you searched would be
 * telling you that you had left the section.
 */
function isActive(href: string): boolean {
    return href !== '#' && page.url.split('?')[0] === href;
}
</script>

<template>
    <header class="border-b border-slate-200 bg-white">
        <div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <div class="flex items-center gap-6">
                <Link
                    :href="dashboard()"
                    class="text-xl font-extrabold whitespace-nowrap text-emerald-600 sm:text-2xl"
                >
                    پنل مدیریت
                </Link>

                <nav
                    aria-label="بخش‌های پنل"
                    class="hidden items-center gap-1 md:flex"
                >
                    <AdminNavLink
                        v-for="item in navItems"
                        :key="item.label"
                        :href="item.href"
                        :active="isActive(item.href)"
                    >
                        {{ item.label }}
                    </AdminNavLink>
                </nav>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    :href="home()"
                    aria-label="بازگشت به سایت"
                    class="flex size-[38px] items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition-colors hover:border-emerald-200 hover:bg-emerald-50 focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none"
                >
                    <HomeIcon />
                </Link>

                <button
                    type="button"
                    aria-controls="admin-mobile-nav"
                    :aria-expanded="isMenuOpen"
                    :aria-label="isMenuOpen ? 'بستن منو' : 'باز کردن منو'"
                    class="flex size-[38px] items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition-colors hover:border-emerald-200 hover:bg-emerald-50 focus-visible:ring-2 focus-visible:ring-emerald-500/40 focus-visible:outline-none md:hidden"
                    @click="isMenuOpen = !isMenuOpen"
                >
                    <CloseIcon v-if="isMenuOpen" />
                    <MenuIcon v-else />
                </button>
            </div>
        </div>

        <nav
            v-show="isMenuOpen"
            id="admin-mobile-nav"
            aria-label="بخش‌های پنل"
            class="flex flex-col gap-1 border-t border-slate-200 px-5 py-3 sm:px-8 md:hidden"
        >
            <AdminNavLink
                v-for="item in navItems"
                :key="item.label"
                :href="item.href"
                :active="isActive(item.href)"
                class="block"
                @click="isMenuOpen = false"
            >
                {{ item.label }}
            </AdminNavLink>
        </nav>
    </header>
</template>
