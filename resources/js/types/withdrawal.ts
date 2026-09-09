export type WithdrawalStatus = 'requested' | 'accepted' | 'paid' | 'rejected';

export type Withdrawal = {
    id: number;
    amount: number;
    fee: number;
    sheba: string;
    status: WithdrawalStatus;
    note: string;
    requested_at: string;
    decided_at: string | null;
};
