<?php

namespace App\Middleware;

use App\Models\User;

class AuthorizeStudent extends AuthorizeProfile
{
    protected function profile(): string
    {
        return User::PROFILE_STUDENT;
    }
}
