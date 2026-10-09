<?php

namespace Tests\Integration\Controllers;

use App\Models\User;

class AdminControllerTest extends ControllerTestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = new User([
            'profile' => User::PROFILE_ADMIN,
            'name' => 'Técnico 1',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $this->user->save();
        $_SESSION['user']['id'] = $this->user->id;
    }

    public function test_render_area_page(): void
    {
        $response = $this->get(action: 'index', controllerName: 'App\Controllers\AdminController');

        $this->assertMatchesRegularExpression('/<h1>\s*Área Administrativa\s*<\/h1>/', $response);
        $this->assertMatchesRegularExpression("/Bem-vindo\(a\), {$this->user->name}! Esta é a área administrativa./", $response);
        $this->assertMatchesRegularExpression('/Sair/', $response);
    }
}
