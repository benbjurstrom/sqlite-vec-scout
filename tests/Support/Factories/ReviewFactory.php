<?php

namespace BenBjurstrom\SqliteVecScout\Tests\Support\Factories;

use BenBjurstrom\SqliteVecScout\Tests\Support\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'product_id' => $this->faker->uuid,
            'user_id' => $this->faker->uuid,
            'profile_name' => $this->faker->userName,
            'helpfulness_numerator' => $this->faker->numberBetween(0, 100),
            'helpfulness_denominator' => $this->faker->numberBetween(1, 100),
            'score' => $this->faker->numberBetween(1, 5),
            'time' => $this->faker->unixTime,
            'summary' => $this->faker->sentence,
            'text' => $this->faker->paragraph,
        ];
    }
}
