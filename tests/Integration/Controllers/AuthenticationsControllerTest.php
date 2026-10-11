<?php

namespace Tests\Integration\Controllers;

use App\Models\User;

class AuthenticationsControllerTest extends ControllerTestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        unset($_SESSION['flash']);

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
        unset($_SESSION['flash']);
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

        $this->assertEquals('Location: /', $response);
        $this->assertEquals($this->user->id, $_SESSION['user']['id']);
        $this->assertEquals('Login realizado com sucesso!', $_SESSION['flash']['success']);
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
}
