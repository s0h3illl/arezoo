import type { User } from './user';

export type Auth = {
    /** Null for a guest: every page outside the panel is reachable signed out. */
    user: User | null;
};
