import type { InjectionKey, Ref } from 'vue';
import { inject, onUnmounted, provide, ref } from 'vue';

import type { HeaderNavItem } from '@/types';

/**
 * The header's page-specific links, passed the only direction Inertia leaves
 * open.
 *
 * Inertia hands a page's template to its layout as the default slot and nothing
 * else, so a page cannot pass its own nav down as markup. The layout provides
 * an empty list instead, the page fills it in, and the header reads it — the
 * landing page owns the section anchors it links to, every other page leaves
 * the list empty and gets the way back home.
 */
const headerNavKey: InjectionKey<Ref<HeaderNavItem[]>> = Symbol('headerNav');

/** Called by the layout, above both the header that reads the list and the page that fills it. */
export function provideHeaderNav(): void {
    provide(headerNavKey, ref([]));
}

/**
 * Called by a page to name its own header links. Emptied again when the page
 * unmounts, so its links don't outlive it on the next Inertia visit.
 */
export function setHeaderNav(items: HeaderNavItem[]): void {
    const nav = useHeaderNav();

    nav.value = items;

    onUnmounted(() => {
        nav.value = [];
    });
}

/** Called by the header. Falls back to an empty list outside a providing layout. */
export function useHeaderNav(): Ref<HeaderNavItem[]> {
    return inject(headerNavKey, ref([]));
}
