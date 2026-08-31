/**
 * PROTOTYPE — throwaway. Ticket .scratch/dashboard/issues/06-the-account-menu-in-the-header.md
 *
 * Three variants of the header account menu, switchable via ?variant=, plus the
 * knobs the ticket asks questions about: what an unverified user sees, the
 * unread count, and whether the trigger carries an avatar.
 */
import { ref, watch } from 'vue';

export type VariantKey = 'A' | 'B' | 'C';
export type UnverifiedTreatment = 'hidden' | 'disabled' | 'live';

export const variantNames: Record<VariantKey, string> = {
    A: 'فهرست ساده — آیکن ۳۸px',
    B: 'کارت هویت — آواتار به‌جای آیکن',
    C: 'کشوی کناری — یک رفتار در همه‌ی اندازه‌ها',
};

const search =
    typeof window === 'undefined'
        ? new URLSearchParams()
        : new URLSearchParams(window.location.search);

function read<T extends string>(key: string, fallback: T, allowed: T[]): T {
    const value = search.get(key) as T | null;

    return value && allowed.includes(value) ? value : fallback;
}

export const variant = ref<VariantKey>(read('variant', 'A', ['A', 'B', 'C']));
export const unverified = ref<UnverifiedTreatment>(
    read('unverified', 'disabled', ['hidden', 'disabled', 'live']),
);
export const isVerified = ref(search.get('verified') !== '0');
export const unread = ref(Number(search.get('unread') ?? 3));
export const hasAvatar = ref(search.get('avatar') !== '0');

/** Reload-stable and shareable, without an Inertia visit that would refetch props. */
watch([variant, unverified, isVerified, unread, hasAvatar], () => {
    const next = new URLSearchParams(window.location.search);

    next.set('variant', variant.value);
    next.set('unverified', unverified.value);
    next.set('verified', isVerified.value ? '1' : '0');
    next.set('unread', String(unread.value));
    next.set('avatar', hasAvatar.value ? '1' : '0');

    window.history.replaceState(
        window.history.state,
        '',
        `${window.location.pathname}?${next}`,
    );
});

/** A stand-in face: avatars are not uploadable yet (ticket 01 is unbuilt). */
export function fakeAvatar(name: string): string {
    const initial = name.trim().charAt(0) || '؟';

    return `data:image/svg+xml;utf8,${encodeURIComponent(
        `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#34d399"/><stop offset="1" stop-color="#0f766e"/></linearGradient></defs><rect width="96" height="96" fill="url(#g)"/><circle cx="48" cy="38" r="16" fill="rgba(255,255,255,0.85)"/><path d="M16 96c0-18 14-30 32-30s32 12 32 30z" fill="rgba(255,255,255,0.85)"/><text x="48" y="88" font-size="18" text-anchor="middle" fill="#064e3b" font-family="sans-serif">${initial}</text></svg>`,
    )}`;
}
