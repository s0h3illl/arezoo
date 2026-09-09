<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWithdrawalNoteRequest extends FormRequest
{
    private const int MAX_NOTE_CHARACTERS = 2_000;

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
            'note' => ['present', 'nullable', 'string', 'max:'.self::MAX_NOTE_CHARACTERS],
        ];
    }

    public function note(): string
    {
        return (string) $this->validated('note');
    }
}
