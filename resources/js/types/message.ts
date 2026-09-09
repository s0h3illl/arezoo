import type { User } from './user';
import type { Wish } from './wish';

export type Message = {
    id: number;
    state: 'visible' | 'anonymous' | 'deleted';
    contributor: Pick<User, 'name' | 'avatar'>;
    message: string | null;
    wish: Pick<Wish, 'title'> & { deleted: boolean };
    amount: number;
    settled_at: string | null;
};
