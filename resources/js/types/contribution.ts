import type { User } from './user';

export type Contribution = {
    id: number;
    state: 'visible' | 'anonymous';
    contributor: Pick<User, 'name' | 'avatar'>;
    amount: number;
    settled_at: string | null;
};
