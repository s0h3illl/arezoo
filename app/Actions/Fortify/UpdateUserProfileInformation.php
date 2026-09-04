<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use RuntimeException;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    use UsernameValidationRules;

    private const int MAX_BIO_CHARACTERS = 500;

    private const int MAX_AVATAR_KILOBYTES = 512;

    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'username' => $this->usernameRules(ignoring: $user),

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],

            'bio' => ['nullable', 'string', 'max:'.self::MAX_BIO_CHARACTERS],

            'avatar' => ['nullable', 'image', 'max:'.self::MAX_AVATAR_KILOBYTES],
        ])->validateWithBag('updateProfileInformation');

        $replacement = $input['avatar'] ?? null;

        $attributes = [
            'name' => $input['name'],
            'username' => $input['username'],
            'email' => $input['email'],
            'bio' => array_key_exists('bio', $input) ? $input['bio'] : $user->bio,
            ...($replacement instanceof UploadedFile
                ? ['avatar' => $this->storeAvatar($replacement, $user->avatar)]
                : []),
        ];

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $attributes);

            return;
        }

        $user->forceFill($attributes)->save();
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function updateVerifiedUser(User $user, array $attributes): void
    {
        $user->forceFill([
            ...$attributes,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    /**
     * @throws RuntimeException when the replacement cannot be written to the disk.
     */
    private function storeAvatar(UploadedFile $replacement, ?string $current): string
    {
        $stored = $replacement->store('avatars', 'public');

        if ($stored === false) {
            throw new RuntimeException('Unable to store the replacement avatar.');
        }

        if ($current !== null) {
            Storage::disk('public')->delete($current);
        }

        return $stored;
    }
}
