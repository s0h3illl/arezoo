import type { User } from './user';
import type { Wish } from './wish';

export type Message = {
    id: number;
    state: 'visible' | 'anonymous';
    contributor: Pick<User, 'name' | 'avatar'>;
    message: string | null;
    wish: Pick<Wish, 'title'>;
    amount: number;
    settled_at: string | null;
};
