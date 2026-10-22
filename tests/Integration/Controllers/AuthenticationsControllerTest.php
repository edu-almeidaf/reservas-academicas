<?php

namespace Tests\Integration\Controllers;

use App\Models\User;
use Lib\Authentication\Auth;

class AuthenticationsControllerTest extends ControllerTestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        unset($_SESSION['flash'], $_SESSION['login_code']);

        $this->user = new User([
            'profile' => User::PROFILE_STUDENT,
            'name' => 'User 1',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $this->user->save();
    }

    public function tearDown(): void
    {
        unset($_SESSION['flash'], $_SESSION['login_code']);
        parent::tearDown();
    }

    public function test_render_login_page(): void
    {
        $response = $this->get(
            action: 'new',
            controllerName: 'App\Controllers\AuthenticationsController'
        );

        $this->assertMatchesRegularExpression('/<form action="\/login" method="POST">/', $response);
        $this->assertMatchesRegularExpression('/name="user\[email\]"/', $response);
        $this->assertMatchesRegularExpression('/name="user\[password\]"/', $response);
        $this->assertMatchesRegularExpression('/value="Entrar"/', $response);
    }

    public function test_successfully_authenticate(): void
    {
        $response = $this->post(
            action: 'authenticate',
            controllerName: 'App\Controllers\AuthenticationsController',
            params: ['user' => ['email' => 'fulano@example.com', 'password' => '123456']]
        );

        $this->assertEquals('Location: /login/code', $response);
        $this->assertFalse(isset($_SESSION['user']['id']));
        $this->assertEquals($this->user->id, $_SESSION['login_code']['user_id']);
        $this->assertMatchesRegularExpression('/^\d{8}$/', $_SESSION['login_code']['code']);
        $this->assertEquals(
            "Código de verificação enviado: {$_SESSION['login_code']['code']}",
            $_SESSION['flash']['success']
        );
    }

    public function test_render_code_page_with_pending_code(): void
    {
        Auth::startCodeVerification($this->user);

        $response = $this->get(action: 'code', controllerName: 'App\Controllers\AuthenticationsController');

        $this->assertMatchesRegularExpression('/<form action="\/login\/code" method="POST">/', $response);
        $this->assertMatchesRegularExpression('/name="code"/', $response);
        $this->assertMatchesRegularExpression('/value="Verificar"/', $response);
    }

    public function test_code_page_without_pending_code_redirects_to_login(): void
    {
        $response = $this->get(action: 'code', controllerName: 'App\Controllers\AuthenticationsController');

        $this->assertEquals('Location: /login', $response);
    }

    public function test_successfully_verify_code(): void
    {
        $code = Auth::startCodeVerification($this->user);

        $response = $this->post(
            action: 'verifyCode',
            controllerName: 'App\Controllers\AuthenticationsController',
            params: ['code' => $code]
        );

        $this->assertEquals('Location: /', $response);
        $this->assertEquals($this->user->id, $_SESSION['user']['id']);
        $this->assertFalse(isset($_SESSION['login_code']));
        $this->assertEquals('Login realizado com sucesso!', $_SESSION['flash']['success']);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidCodeProvider(): array
    {
        return [
            'wrong code' => ['wrong'],
            'too short' => ['1234567'],
            'not a number' => ['abcdefgh'],
            'not a string' => [['12345678']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidCodeProvider')]
    public function test_unsuccessfully_verify_code(mixed $code): void
    {
        $validCode = Auth::startCodeVerification($this->user);
        if ($code === 'wrong') {
            $code = str_pad((string) (((int) $validCode + 1) % 100000000), 8, '0', STR_PAD_LEFT);
        }

        $response = $this->post(
            action: 'verifyCode',
            controllerName: 'App\Controllers\AuthenticationsController',
            params: ['code' => $code]
        );

        $this->assertEquals('Location: /login/code', $response);
        $this->assertFalse(isset($_SESSION['user']['id']));
        $this->assertTrue(Auth::hasPendingCode());
        $this->assertEquals('Código de verificação inválido!', $_SESSION['flash']['danger']);
    }

    public function test_unsuccessfully_authenticate_with_wrong_password(): void
    {
        $response = $this->post(
            action: 'authenticate',
            controllerName: 'App\Controllers\AuthenticationsController',
            params: ['user' => ['email' => 'fulano@example.com', 'password' => 'wrong']]
        );

        $this->assertEquals('Location: /login', $response);
        $this->assertFalse(isset($_SESSION['user']['id']));
        $this->assertEquals('Email e/ou senha inválidos!', $_SESSION['flash']['danger']);
    }

    public function test_unsuccessfully_authenticate_with_nonexistent_email(): void
    {
        $response = $this->post(
            action: 'authenticate',
            controllerName: 'App\Controllers\AuthenticationsController',
            params: ['user' => ['email' => 'not.exists@example.com', 'password' => '123456']]
        );

        $this->assertEquals('Location: /login', $response);
        $this->assertFalse(isset($_SESSION['user']['id']));
        $this->assertEquals('Email e/ou senha inválidos!', $_SESSION['flash']['danger']);
    }

    public function test_unsuccessfully_authenticate_without_params(): void
    {
        $response = $this->post(
            action: 'authenticate',
            controllerName: 'App\Controllers\AuthenticationsController'
        );

        $this->assertEquals('Location: /login', $response);
        $this->assertFalse(isset($_SESSION['user']['id']));
        $this->assertEquals('Email e/ou senha inválidos!', $_SESSION['flash']['danger']);
    }

    public function test_logout(): void
    {
        $_SESSION['user']['id'] = $this->user->id;

        $response = $this->post(
            action: 'destroy',
            controllerName: 'App\Controllers\AuthenticationsController'
        );

        $this->assertEquals('Location: /login', $response);
        $this->assertFalse(isset($_SESSION['user']['id']));
        $this->assertEquals('Logout realizado com sucesso!', $_SESSION['flash']['success']);
    }

    public function test_verify_code_should_register_the_login(): void
    {
        $code = Auth::startCodeVerification($this->user);

        $this->post(
            action: 'verifyCode',
            controllerName: 'App\Controllers\AuthenticationsController',
            params: ['code' => $code]
        );

        $this->assertNotNull(User::findById($this->user->id)->last_login_at);
        $this->assertEquals('Login realizado com sucesso!', $_SESSION['flash']['success']);
    }

    public function test_verify_code_should_show_the_previous_login(): void
    {
        $this->user->update(['last_login_at' => '2026-10-20 08:15:00']);
        $code = Auth::startCodeVerification($this->user);

        $this->post(
            action: 'verifyCode',
            controllerName: 'App\Controllers\AuthenticationsController',
            params: ['code' => $code]
        );

        $this->assertEquals(
            'Login realizado com sucesso! Último acesso em 20/10/2026 às 08:15.',
            $_SESSION['flash']['success']
        );
    }

    private function authenticateWith(string $password): string
    {
        return $this->post(
            action: 'authenticate',
            controllerName: 'App\Controllers\AuthenticationsController',
            params: ['user' => ['email' => 'fulano@example.com', 'password' => $password]]
        );
    }

    public function test_wrong_password_should_count_a_failed_attempt(): void
    {
        $this->authenticateWith('wrong');

        $this->assertSame(1, User::findById($this->user->id)->failed_login_attempts);
    }

    public function test_user_should_be_locked_after_too_many_attempts(): void
    {
        for ($i = 0; $i < User::MAX_LOGIN_ATTEMPTS; $i++) {
            $this->authenticateWith('wrong');
        }

        $response = $this->authenticateWith('123456');

        $this->assertEquals('Location: /login', $response);
        $this->assertFalse(Auth::hasPendingCode());
        $this->assertEquals(
            'Muitas tentativas de login. Tente novamente em ' . User::LOCK_MINUTES . ' minutos.',
            $_SESSION['flash']['danger']
        );
    }

    public function test_unknown_email_does_not_fail(): void
    {
        $response = $this->post(
            action: 'authenticate',
            controllerName: 'App\Controllers\AuthenticationsController',
            params: ['user' => ['email' => 'ninguem@example.com', 'password' => 'wrong']]
        );

        $this->assertEquals('Location: /login', $response);
        $this->assertEquals('Email e/ou senha inválidos!', $_SESSION['flash']['danger']);
    }
}
