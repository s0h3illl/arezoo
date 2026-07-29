<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\Seeder;

class WishSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $users->each(function (User $user): void {
            Wish::factory()
                ->count(fake()->numberBetween(3, 8))
                ->for($user, 'owner')
                ->create();

            Wish::factory()
                ->withoutThumbnail()
                ->for($user, 'owner')
                ->create();
        });
    }
}
