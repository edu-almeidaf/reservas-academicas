<?php

namespace App\Controllers;

use App\Models\User;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class UsersController extends Controller
{
    private const SIGNUP_FIELDS = ['profile', 'name', 'email', 'password', 'password_confirmation'];

    protected string $layout = 'login';

    public function new(): void
    {
        $user = new User();

        $title = 'Cadastro';
        $profileOptions = User::signupProfileOptions();
        $this->render('users/new', compact('user', 'title', 'profileOptions'));
    }

    public function create(Request $request): void
    {
        $user = new User($this->signupAttributes($request->getParam('user')));

        if ($user->isValidForSignup() && $user->save()) {
            FlashMessage::success('Cadastro realizado com sucesso! Faça login para continuar.');
            $this->redirectTo(route('users.login'));
        } else {
            FlashMessage::danger('Existem dados incorretos! Por favor, verifique!');
            $title = 'Cadastro';
            $profileOptions = User::signupProfileOptions();
            $this->render('users/new', compact('user', 'title', 'profileOptions'));
        }
    }

    /**
     * @return array<string, string>
     */
    private function signupAttributes(mixed $params): array
    {
        if (!is_array($params)) {
            return [];
        }

        $attributes = [];
        foreach (self::SIGNUP_FIELDS as $field) {
            if (isset($params[$field]) && is_string($params[$field])) {
                $attributes[$field] = $params[$field];
            }
        }

        return $attributes;
    }
}
