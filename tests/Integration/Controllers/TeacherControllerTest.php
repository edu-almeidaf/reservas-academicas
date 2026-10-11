<?php

namespace Tests\Integration\Controllers;

use App\Models\User;

class TeacherControllerTest extends ControllerTestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = new User([
            'profile' => User::PROFILE_TEACHER,
            'name' => 'Docente 1',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $this->user->save();
        $_SESSION['user']['id'] = $this->user->id;
    }

    public function test_render_area_page(): void
    {
        $response = $this->get(action: 'index', controllerName: 'App\Controllers\TeacherController');

        $this->assertMatchesRegularExpression('/<h1>\s*Área do Docente\s*<\/h1>/', $response);
        $this->assertMatchesRegularExpression("/Bem-vindo\(a\), {$this->user->name}! Esta é a área do docente./", $response);
        $this->assertMatchesRegularExpression('/Sair/', $response);
    }
}
