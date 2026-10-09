<?php

namespace Config;

class App
{
    public static array $middlewareAliases = [
        'auth' => \App\Middleware\Authenticate::class,
        'student' => \App\Middleware\AuthorizeStudent::class,
        'teacher' => \App\Middleware\AuthorizeTeacher::class,
        'admin' => \App\Middleware\AuthorizeAdmin::class,
    ];
}
