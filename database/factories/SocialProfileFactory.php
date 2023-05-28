<?php

namespace Database\Factories;

use App\Models\SocialProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SocialProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SocialProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'provider' => $this->faker->randomElement(['facebook', 'twitter', 'google', 'apple']),
            'provider_id' => Str::random(16),
            'nickname' => $this->faker->optional()->userName,
            'name' => $this->faker->optional()->name,
            'email' => $this->faker->unique()->safeEmail,
            'avatar' => $this->faker->optional()->imageUrl(1000,1000, 'avatar', true),
            'token' => Str::random(32),
        ];
    }
}
