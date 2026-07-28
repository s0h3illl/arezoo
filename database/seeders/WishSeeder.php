<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wish;

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
                ->forOwner($user)
                ->create();

            Wish::factory()
                ->withoutThumbnail()
                ->forOwner($user)
                ->create();
        });
    }
}
