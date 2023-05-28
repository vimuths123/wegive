<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationProgramFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrganizationProgram::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'organization_id' => Organization::factory(),
            'return_date' => now(), // TODO: figure out how to set this so it makes sense
            'description' => $this->faker->sentences(3, true),
            'revenue' => $this->faker->randomNumber(7),
            'grant_expenses' => $this->faker->randomNumber(7),
            'total_expenses' => $this->faker->randomNumber(7),
        ];
    }
}
