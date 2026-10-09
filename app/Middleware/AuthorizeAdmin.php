<?php

namespace App\Middleware;

use App\Models\User;

class AuthorizeAdmin extends AuthorizeProfile
{
    protected function profile(): string
    {
        return User::PROFILE_ADMIN;
    }
}
