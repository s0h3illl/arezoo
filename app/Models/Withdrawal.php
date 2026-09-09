<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Database\Factories\WithdrawalFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $amount
 * @property int $fee
 * @property string $sheba
 * @property WithdrawalStatus $status
 * @property string $note
 * @property Carbon $requested_at
 * @property Carbon|null $decided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 */
class Withdrawal extends Model
{
    /** @use HasFactory<WithdrawalFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $attributes = [
        'note' => '',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fee' => 'integer',
            'status' => WithdrawalStatus::class,
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function accept(): void
    {
        abort_unless($this->status === WithdrawalStatus::Requested, 403);

        $this->update([
            'status' => WithdrawalStatus::Accepted,
            'decided_at' => now(),
        ]);
    }

    public function markPaid(): void
    {
        abort_unless($this->status === WithdrawalStatus::Accepted, 403);

        $this->update([
            'status' => WithdrawalStatus::Paid,
            'decided_at' => now(),
        ]);
    }

    public function reject(): void
    {
        abort_unless(in_array($this->status, [WithdrawalStatus::Requested, WithdrawalStatus::Accepted], true), 403);

        $this->update([
            'status' => WithdrawalStatus::Rejected,
            'decided_at' => now(),
        ]);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function reserving(Builder $query): void
    {
        $query->where('status', '!=', WithdrawalStatus::Rejected);
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', [WithdrawalStatus::Requested, WithdrawalStatus::Accepted]);
    }
}
