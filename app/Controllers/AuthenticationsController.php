<?php

namespace App\Controllers;

use App\Models\User;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\Authentication\Auth;
use Lib\FlashMessage;

class AuthenticationsController extends Controller
{
    protected string $layout = 'login';

    public function new(): void
    {
        $this->render('authentications/new');
    }

    public function authenticate(Request $request): void
    {
        $credentials = $this->credentials($request->getParam('user'));
        $user = $credentials ? User::findByEmail($credentials['email']) : null;

        if ($user && $user->authenticate($credentials['password'])) {
            Auth::login($user);

            FlashMessage::success('Login realizado com sucesso!');
            $this->redirectTo(route('root'));
        } else {
            FlashMessage::danger('Email e/ou senha inválidos!');
            $this->redirectTo(route('users.login'));
        }
    }

    public function destroy(): void
    {
        Auth::logout();
        FlashMessage::success('Logout realizado com sucesso!');
        $this->redirectTo(route('users.login'));
    }

    /**
     * @return array{email: string, password: string}|null
     */
    private function credentials(mixed $params): ?array
    {
        if (!is_array($params) || !isset($params['email'], $params['password'])) {
            return null;
        }

        $email = $params['email'];
        $password = $params['password'];

        if (!is_string($email) || !is_string($password) || $email === '' || $password === '') {
            return null;
        }

        return ['email' => $email, 'password' => $password];
    }
}
