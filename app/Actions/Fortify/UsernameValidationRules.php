<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

trait UsernameValidationRules
{
    /**
     * Paths a username may never take, because the app routes them itself.
     *
     * A profile lives at `/u/{username}`, so nothing here is shadowed today. The
     * list guards against the day a username is used somewhere shallower, and
     * against handing out a name the app plainly means to keep for itself.
     *
     * @var list<string>
     */
    protected const RESERVED_USERNAMES = [
        'admin',
        'api',
        'arezoo',
        'email',
        'forgot-password',
        'help',
        'login',
        'logout',
        'password',
        'profile',
        'register',
        'reset-password',
        'settings',
        'storage',
        'support',
        'terms',
        'u',
        'up',
        'user',
    ];

    /**
     * Get the validation rules used to validate usernames.
     *
     * The username is the whole of a profile's address, so it is held to the
     * shape of a URL segment: ASCII letters, digits, dashes and underscores, and
     * nothing else. `alpha_dash` alone would accept Persian letters, which no
     * link a person types out by hand would survive.
     *
     * @return array<int, Rule|Closure|array<mixed>|string>
     */
    protected function usernameRules(?User $ignoring = null): array
    {
        return [
            'required',
            'string',
            'alpha_dash:ascii',
            'min:3',
            'max:30',
            function (string $attribute, mixed $value, Closure $fail) use ($ignoring): void {
                $this->validateUsernameIsFree($attribute, $value, $fail, $ignoring);
            },
        ];
    }

    protected function validateUsernameIsFree(string $attribute, mixed $value, Closure $fail, ?User $ignoring = null): void
    {
        if (! is_string($value)) {
            return;
        }

        $username = Str::lower($value);

        if (in_array($username, self::RESERVED_USERNAMES, true)) {
            $fail('validation.custom.username.not_in')->translate();

            return;
        }

        $holders = User::query()->withUsername($username);

        if ($ignoring !== null) {
            $holders->whereKeyNot($ignoring->getKey());
        }

        if ($holders->exists()) {
            $fail('validation.unique')->translate();
        }
    }
}
