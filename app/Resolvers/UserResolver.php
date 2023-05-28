<?php

namespace App\Resolvers;


class UserResolver implements \OwenIt\Auditing\Contracts\UserResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return auth()->user() ? auth()->user() : null;
    }
}
