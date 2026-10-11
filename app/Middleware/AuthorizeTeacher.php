<?php

namespace App\Middleware;

use App\Models\User;

class AuthorizeTeacher extends AuthorizeProfile
{
    protected function profile(): string
    {
        return User::PROFILE_TEACHER;
    }
}
