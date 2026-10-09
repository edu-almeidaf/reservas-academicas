<?php

namespace Tests\Integration\Controllers;

use App\Models\User;

class HomeControllerTest extends ControllerTestCase
{
    public function test_redirect_to_login_if_not_authenticated(): void
    {
        $response = $this->get(action: 'index', controllerName: 'App\Controllers\HomeController');

        $this->assertEquals('Location: /login', $response);
    }

    public function test_redirect_student_to_student_area(): void
    {
        $this->login(User::PROFILE_STUDENT);

        $response = $this->get(action: 'index', controllerName: 'App\Controllers\HomeController');

        $this->assertEquals('Location: /student', $response);
    }

    public function test_redirect_teacher_to_teacher_area(): void
    {
        $this->login(User::PROFILE_TEACHER);

        $response = $this->get(action: 'index', controllerName: 'App\Controllers\HomeController');

        $this->assertEquals('Location: /teacher', $response);
    }

    public function test_redirect_admin_to_admin_area(): void
    {
        $this->login(User::PROFILE_ADMIN);

        $response = $this->get(action: 'index', controllerName: 'App\Controllers\HomeController');

        $this->assertEquals('Location: /admin', $response);
    }

    private function login(string $profile): void
    {
        $user = new User([
            'profile' => $profile,
            'name' => 'User 1',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $user->save();
        $_SESSION['user']['id'] = $user->id;
    }
}
