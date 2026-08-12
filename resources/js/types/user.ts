/**
 * A user as the server serialises them — every column the model does not hide.
 * `password` and `remember_token` are `#[Hidden]` on the model and never cross
 * the wire, so they have no place in this type.
 */
export type User = {
    id: number;
    name: string;
    username: string;
    email: string;
    avatar: string | null;
    bio: string | null;
    email_verified_at: string | null;
    is_admin: boolean;
    is_blocked: boolean;
    created_at: string | null;
    updated_at: string | null;
};
