<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A single attempt to move money through a payment gateway.
 *
 * Every attempt is kept, whether it succeeds or not, so a payment holds no
 * foreign key to its contribution — the contribution points at the payment.
 *
 * @property int $id
 * @property int $amount
 * @property string|null $transaction_id
 * @property string|null $reference_id
 * @property PaymentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contribution|null $contribution
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The contribution created alongside this payment, if it still exists.
     *
     * @return HasOne<Contribution, $this>
     */
    public function contribution(): HasOne
    {
        return $this->hasOne(Contribution::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => PaymentStatus::class,
        ];
    }
}
