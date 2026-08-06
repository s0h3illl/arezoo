/**
 * A gateway attempt as the admin panel reads it — one row of the payments
 * list, mirroring `App\Http\Resources\Admin\PaymentResource`.
 *
 * `contribution` is nullable by design: a failed attempt outlives the
 * contribution it was created alongside, which is deleted rather than kept
 * (ADR-0001), so an attempt with nothing behind it is an ordinary row here.
 */
export type PaymentStatus = 'pending' | 'verified' | 'failed';

export type Payment = {
    id: number;
    transaction_id: string | null;
    reference_id: string | null;
    amount: number;
    status: PaymentStatus;
    created_at: string | null;
    contribution: {
        id: number;
        wish: { id: number; title: string };
        contributor: { id: number; name: string };
    } | null;
};
