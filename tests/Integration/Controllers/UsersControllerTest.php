<?php

namespace Tests\Integration\Controllers;

use App\Models\User;

class UsersControllerTest extends ControllerTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        unset($_SESSION['flash']);
    }

    public function tearDown(): void
    {
        unset($_SESSION['flash']);
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function userParams(array $overrides = []): array
    {
        return ['user' => array_merge([
            'profile' => User::PROFILE_STUDENT,
            'name' => 'Fulano',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456',
        ], $overrides)];
    }

    public function test_render_signup_page(): void
    {
        $response = $this->get(action: 'new', controllerName: 'App\Controllers\UsersController');

        $this->assertMatchesRegularExpression('/<form action="\/signup" method="POST"/', $response);
        $this->assertMatchesRegularExpression('/name="user\[name\]"/', $response);
        $this->assertMatchesRegularExpression('/name="user\[email\]"/', $response);
        $this->assertMatchesRegularExpression('/name="user\[profile\]" value="discente"/', $response);
        $this->assertMatchesRegularExpression('/name="user\[profile\]" value="docente"/', $response);
        $this->assertDoesNotMatchRegularExpression('/value="tecnico"/', $response);
        $this->assertMatchesRegularExpression('/name="user\[password\]"/', $response);
        $this->assertMatchesRegularExpression('/name="user\[password_confirmation\]"/', $response);
        $this->assertMatchesRegularExpression('/value="Cadastrar"/', $response);
    }

    public function test_successfully_create_user(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\UsersController',
            params: $this->userParams()
        );

        $this->assertEquals('Location: /login', $response);
        $this->assertEquals('Cadastro realizado com sucesso! Faça login para continuar.', $_SESSION['flash']['success']);

        $user = User::findByEmail('fulano@example.com');
        $this->assertNotNull($user);
        $this->assertEquals(User::PROFILE_STUDENT, $user->profile);
        $this->assertTrue($user->authenticate('123456'));
    }

    public function test_unsuccessfully_create_user_with_invalid_data(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\UsersController',
            params: $this->userParams(['name' => '', 'email' => 'invalid', 'password_confirmation' => 'other'])
        );

        $this->assertStringContainsString('Existem dados incorretos! Por favor, verifique!', $response);
        $this->assertStringContainsString('não pode ser vazio!', $response);
        $this->assertStringContainsString('não é um e-mail válido!', $response);
        $this->assertStringContainsString('as senhas devem ser idênticas!', $response);
        $this->assertCount(0, User::all());
    }

    public function test_unsuccessfully_create_user_without_params(): void
    {
        $response = $this->post(action: 'create', controllerName: 'App\Controllers\UsersController');

        $this->assertStringContainsString('Existem dados incorretos! Por favor, verifique!', $response);
        $this->assertStringContainsString('não pode ser vazio!', $response);
        $this->assertCount(0, User::all());
    }

    public function test_unsuccessfully_create_user_with_duplicated_email(): void
    {
        $this->post(action: 'create', controllerName: 'App\Controllers\UsersController', params: $this->userParams());

        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\UsersController',
            params: $this->userParams(['name' => 'Outro'])
        );

        $this->assertStringContainsString('já existe um registro com esse dado', $response);
        $this->assertCount(1, User::all());
    }

    public function test_unsuccessfully_create_user_with_admin_profile(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\UsersController',
            params: $this->userParams(['profile' => User::PROFILE_ADMIN])
        );

        $this->assertStringContainsString('Existem dados incorretos! Por favor, verifique!', $response);
        $this->assertStringContainsString('não é um valor válido!', $response);
        $this->assertNull(User::findByEmail('fulano@example.com'));
    }

    public function test_forged_id_is_ignored(): void
    {
        $existing = new User([
            'profile' => User::PROFILE_TEACHER,
            'name' => 'Existente',
            'email' => 'existente@example.com',
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);
        $existing->save();

        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\UsersController',
            params: $this->userParams(['id' => $existing->id])
        );

        $this->assertEquals('Location: /login', $response);
        $this->assertCount(2, User::all());
        $this->assertEquals('Existente', User::findById($existing->id)->name);
    }
}
