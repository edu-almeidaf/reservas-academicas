<?php

namespace App\Controllers;

use App\Models\User;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class UsersController extends Controller
{
    protected string $layout = 'login';

    public function new(): void
    {
        $user = new User();

        $title = 'Cadastro';
        $this->render('users/new', compact('user', 'title'));
    }

    public function create(Request $request): void
    {
        $params = $request->getParam('user', []);

        // Only allowed keys: a forged user[id] would turn the insert into an update
        $user = new User([
            'profile' => $params['profile'] ?? null,
            'name' => $params['name'] ?? null,
            'email' => $params['email'] ?? null,
            'password' => $params['password'] ?? null,
            'password_confirmation' => $params['password_confirmation'] ?? null,
        ]);

        if ($user->isValidForSignup() && $user->save()) {
            FlashMessage::success('Cadastro realizado com sucesso! Faça login para continuar.');
            $this->redirectTo(route('users.login'));
        } else {
            FlashMessage::danger('Existem dados incorretos! Por favor, verifique!');
            $title = 'Cadastro';
            $this->render('users/new', compact('user', 'title'));
        }
    }
}
