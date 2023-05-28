<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {


        return [
            'amount' => rand(100, 10000),
            'description' => 'Seeded',
            'status' => 2,
        ];
    }
}
