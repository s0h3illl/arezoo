<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * The value of the `action` field that marks a request as a block toggle.
     */
    public const BLOCK_ACTION = 'block';

    /**
     * Determine if the user is authorized to make this request.
     *
     * The admin gate in front of the whole section is the authorization; there
     * is no second rule about which users an admin may act on.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Blocking is a single decision taken from a row in a list, so it arrives on
     * its own and says which way it goes. Every other update is the edit form,
     * where the person is being described and the block state is not in play.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isBlockAction()) {
            return [
                'action' => ['required', Rule::in([self::BLOCK_ACTION])],
                'is_blocked' => ['required', 'boolean'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->route('user')),
            ],
        ];
    }

    /**
     * Whether this request only means to block or unblock the user.
     */
    public function isBlockAction(): bool
    {
        return $this->input('action') === self::BLOCK_ACTION;
    }
}
