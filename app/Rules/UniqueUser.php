<?php

namespace App\Rules;

use App\Models\SocialProfile;
use Illuminate\Contracts\Validation\Rule;

class UniqueUser implements Rule
{
    protected $provider;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // $socialProfile = SocialProfile::whereEmail($value)->first();
        // if ($socialProfile) {
        //     $this->provider = $socialProfile->provider;
        //     return false;
        // }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "A user account with that email has already registered via {$this->provider}.";
    }
}
