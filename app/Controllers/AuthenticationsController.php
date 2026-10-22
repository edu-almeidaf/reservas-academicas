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
        $params = $request->getParam('user', []);
        $user = User::findByEmail($params['email'] ?? '');

        if ($user && $user->authenticate($params['password'] ?? '')) {
            $code = Auth::startCodeVerification($user);

            FlashMessage::success("Código de verificação enviado: {$code}");
            $this->redirectTo(route('users.code'));
        } else {
            FlashMessage::danger('Email e/ou senha inválidos!');
            $this->redirectTo(route('users.login'));
        }
    }

    public function code(): void
    {
        if (!Auth::hasPendingCode()) {
            $this->redirectTo(route('users.login'));
            return;
        }

        $title = 'Código de verificação';
        $this->render('authentications/code', compact('title'));
    }

    public function verifyCode(Request $request): void
    {
        $code = $request->getParam('code');
        $isValidFormat = is_string($code) && ctype_digit($code) && strlen($code) === Auth::CODE_LENGTH;
        $user = $isValidFormat ? Auth::verifyCode($code) : null;

        if ($user) {
            $lastLogin = $user->lastLoginLabel();
            $user->registerLogin();
            Auth::login($user);

            $message = 'Login realizado com sucesso!';
            if ($lastLogin !== null) {
                $message .= " Último acesso em {$lastLogin}.";
            }

            FlashMessage::success($message);
            $this->redirectTo(route('root'));
        } else {
            FlashMessage::danger('Código de verificação inválido!');
            $this->redirectTo(route('users.code'));
        }
    }

    public function destroy(): void
    {
        Auth::logout();
        FlashMessage::success('Logout realizado com sucesso!');
        $this->redirectTo(route('users.login'));
    }
}
