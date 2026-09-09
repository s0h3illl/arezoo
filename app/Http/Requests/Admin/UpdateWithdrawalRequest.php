<?php

namespace App\Http\Requests\Admin;

use App\Enums\WithdrawalDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWithdrawalRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(WithdrawalDecision::class)],
        ];
    }

    public function decision(): WithdrawalDecision
    {
        return WithdrawalDecision::from((string) $this->validated('action'));
    }
}
