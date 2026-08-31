/** PROTOTYPE — throwaway. See usePrototypeState.ts. */
import { profile } from '@/routes';

export type AccountMenuItem = {
    label: string;
    href: string;
    /** `/dashboard/*` sits behind `verified`; «صفحه من» does not. */
    needsVerification: boolean;
    badge?: boolean;
};

/** The three dashboard routes are unbuilt, so they hold '#', as AdminHeader does. */
export function accountMenuItems(username: string): AccountMenuItem[] {
    return [
        {
            label: 'صفحه من',
            href: profile(username).url,
            needsVerification: false,
        },
        { label: 'اطلاعات من', href: '#', needsVerification: true },
        { label: 'پیام‌ها', href: '#', needsVerification: true, badge: true },
        { label: 'برداشت‌ها', href: '#', needsVerification: true },
    ];
}
