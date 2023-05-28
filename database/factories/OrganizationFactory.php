<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->unique()->company;
        return [
            'legal_name' =>  $name,
            'dba' =>  $name,
            'ein' => $this->faker->unique()->ein,
            'tl_token' => "acct_hzt4GmgYZmenq6WNewgN4"
        ];
    }
}
