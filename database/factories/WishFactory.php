<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * @extends Factory<Wish>
 */
class WishFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'thumbnail' => $this->storeThumbnail(),
            'purchase_link' => fake()->url(),
            'price' => fake()->numberBetween(100, 2_000) * 1_000,
        ];
    }

    /**
     * Store a copy of the fixture cover on the public disk, for this wish alone.
     *
     * @throws RuntimeException when the fixture cannot be written to the disk.
     */
    protected function storeThumbnail(): string
    {
        $path = Storage::disk('public')->putFile(
            'wishes', new File(base_path('tests/fixtures/wish-cover.jpg'))
        );

        if ($path === false) {
            throw new RuntimeException('Unable to store the wish fixture thumbnail.');
        }

        return $path;
    }

    /**
     * Indicate that the wish has no thumbnail image.
     */
    public function withoutThumbnail(): static
    {
        return $this->state(fn (array $attributes) => [
            'thumbnail' => null,
        ]);
    }
}
