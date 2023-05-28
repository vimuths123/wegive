<?php

namespace Database\Factories;

use App\Models\Givelist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GivelistFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Givelist::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => Str::ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->sentences(2, true),
            'active' => true,
            'is_public' => true,
        ];
    }

    public function withUser()
    {
        return $this->state(fn () => ['user_id' => User::factory()]);
    }

    public function inactive()
    {
        return $this->state(fn () => ['active' => false]);
    }

    public function private()
    {
        return $this->state(fn () => ['is_public' => false]);
    }


}
