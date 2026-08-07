<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_admin
 * @property bool $is_blocked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Each column's database default only lands after a round trip, so the model
     * carries it too — a user is never momentarily an admin, or a blocked user,
     * of unknown status.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_admin' => false,
        'is_blocked' => false,
    ];

    /**
     * Scope a query to the platform's users, leaving out the admins.
     *
     * An admin is staff, not one of the people the platform serves, and is not
     * managed from the panel at all — see ADR-0008.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function excludingAdmins(Builder $query): void
    {
        $query->where('is_admin', false);
    }

    /**
     * Scope a query to users whose name or email contains the given term.
     *
     * Name and email are the two things an admin actually knows about a person,
     * so both are matched by the one term. Wildcards in the term are escaped —
     * a search for `100%` looks for that text, not for everything — which needs
     * an explicit ESCAPE clause, since SQLite assumes no escape character at all.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function matching(Builder $query, string $term): void
    {
        $contains = '%'.addcslashes($term, '%_\\').'%';

        $query->where(function (Builder $query) use ($contains): void {
            $query->whereRaw('name like ? escape ?', [$contains, '\\'])
                ->orWhereRaw('email like ? escape ?', [$contains, '\\']);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_blocked' => 'boolean',
        ];
    }
}
