<?php

namespace App\Http\Requests;

use App\Rules\Sheba;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    private const int MAX_AMOUNT = 4_294_967_295;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:'.$this->minimum(), 'max:'.self::MAX_AMOUNT],
            'sheba' => ['required', 'string', new Sheba],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => (string) __('withdrawals.below_minimum'),
        ];
    }

    private function minimum(): int
    {
        return (int) config('withdrawals.minimum');
    }
}
