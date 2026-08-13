<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property string|null $avatar
 * @property string|null $bio
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_admin
 * @property bool $is_blocked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Wish> $wishes
 */
#[Fillable(['name', 'username', 'email', 'password'])]
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
     * The wishes this user has published, which are the whole of their profile.
     *
     * @return HasMany<Wish, $this>
     */
    public function wishes(): HasMany
    {
        return $this->hasMany(Wish::class);
    }

    /**
     * Store a username in lower case.
     *
     * A username is the whole of a profile's address, and an address that works
     * in several capitalisations is several addresses. Lowering it here means
     * every way in — registration, a factory, a seeder, the settings screen when
     * it lands — leaves the unique index enforcing the rule.
     *
     * @return Attribute<string, string>
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (string $username): string => Str::lower($username),
        );
    }

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
     * Scope a query to the users an admin has not barred from the app.
     *
     * A blocked user is gone as far as everyone else is concerned, so nothing of
     * theirs is reachable — not their profile, not the wishes on it.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function excludingBlocked(Builder $query): void
    {
        $query->where('is_blocked', false);
    }

    /**
     * Scope a query to the user holding the given username.
     *
     * Two usernames differing only in capitalisation are the same username, so
     * the needle is lowered the way every stored username already is. Matching
     * stays an exact comparison, which the unique index can answer.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function withUsername(Builder $query, string $username): void
    {
        $query->where('username', Str::lower($username));
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
