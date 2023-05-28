<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'active' => true,
            //'recurring_payment_source' => User::PAYMENT_SOURCE_BALANCE,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive()
    {
        return $this->state(fn () => ['active' => false]);
    }

    public function withAddress()
    {
        return $this->state(function() {
            return [
                'address1' => $this->faker->streetAddress,
                'address2' => $this->faker->optional()->secondaryAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->stateAbbr,
                'zip' => $this->faker->postcode,
            ];
        });
    }

    /*
    public function paymentSource($paymentSource)
    {
        return $this->state(fn () => ['recurring_payment_source' => $paymentSource]);
    }

    public function balance($amount)
    {
        return $this->state(fn () => ['givelist_balance' => $amount]);
    }

    public function givingFrequency($frequency)
    {
        return $this->state(fn () => ['giving_frequency' => $frequency]);
    }
    */
}
