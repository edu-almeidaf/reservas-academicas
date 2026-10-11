<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        if ($this->current_user === null) {
            $this->redirectTo(route('users.login'));
            return;
        }

        $this->redirectTo(route($this->current_user->homeRouteName()));
    }
}
