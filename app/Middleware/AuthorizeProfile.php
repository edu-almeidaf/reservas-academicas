<?php

namespace App\Middleware;

use Core\Http\Middleware\Middleware;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

abstract class AuthorizeProfile implements Middleware
{
    abstract protected function profile(): string;

    public function handle(Request $request): void
    {
        $user = Auth::user();

        if ($user === null) {
            FlashMessage::danger('Você deve estar logado para acessar essa página');
            $this->redirectTo(route('users.login'));
        }

        if (!$user->hasProfile($this->profile())) {
            FlashMessage::danger('Você não tem permissão para acessar essa página');
            $this->redirectTo(route($user->homeRouteName()));
        }
    }

    private function redirectTo(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}
