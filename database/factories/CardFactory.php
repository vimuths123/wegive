<?php

namespace Database\Factories;

use App\Models\Card;
use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Card::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'tl_token' => 'pm_gCxMnW39yeIYVI8DYgRZo',
            'last_four' => '1111',
            'expiration' => '11/2022',
            'issuer' => 'visa'
        ];
    }
}
