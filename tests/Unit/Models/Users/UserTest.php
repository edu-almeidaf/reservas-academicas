<?php

namespace Tests\Unit\Models\Users;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    private User $user;
    private User $user2;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new User([
            'profile' => 'discente',
            'name' => 'User 1',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $this->user->save();

        $this->user2 = new User([
            'profile' => 'discente',
            'name' => 'User 2',
            'email' => 'fulano1@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $this->user2->save();
    }

    public function test_should_create_new_user(): void
    {
        $this->assertCount(2, User::all());
    }

    public function test_all_should_return_all_users(): void
    {
        $this->user2->save();

        $users[] = $this->user->id;
        $users[] = $this->user2->id;

        $all = array_map(fn($user) => $user->id, User::all());

        $this->assertCount(2, $all);
        $this->assertEquals($users, $all);
    }

    public function test_destroy_should_remove_the_user(): void
    {
        $this->user->destroy();
        $this->assertCount(1, User::all());
    }

    public function test_set_id(): void
    {
        $this->user->id = 10;
        $this->assertEquals(10, $this->user->id);
    }

    public function test_set_name(): void
    {
        $this->user->name = 'User name';
        $this->assertEquals('User name', $this->user->name);
    }

    public function test_set_email(): void
    {
        $this->user->email = 'outro@example.com';
        $this->assertEquals('outro@example.com', $this->user->email);
    }

    public function test_errors_should_return_errors(): void
    {
        $user = new User();

        $this->assertFalse($user->isValid());
        $this->assertFalse($user->save());
        $this->assertTrue($user->hasErrors());

        $this->assertEquals('não pode ser vazio!', $user->errors('name'));
        $this->assertEquals('não pode ser vazio!', $user->errors('email'));
        $this->assertEquals('não é um valor válido!', $user->errors('profile'));
        $this->assertEquals('não pode ser vazio!', $user->errors('password'));
    }

    public function test_errors_should_return_password_confirmation_error(): void
    {
        $user = new User([
            'profile' => 'discente',
            'name' => 'User 3',
            'email' => 'fulano3@example.com',
            'password' => '123456',
            'password_confirmation' => '1234567'
        ]);

        $this->assertFalse($user->isValid());
        $this->assertFalse($user->save());

        $this->assertEquals('as senhas devem ser idênticas!', $user->errors('password'));
    }

    public function test_find_by_id_should_return_the_user(): void
    {
        $this->assertEquals($this->user->id, User::findById($this->user->id)->id);
    }

    public function test_find_by_id_should_return_null(): void
    {
        $this->assertNull(User::findById(3));
    }

    public function test_find_by_email_should_return_the_user(): void
    {
        $this->assertEquals($this->user->id, User::findByEmail($this->user->email)->id);
    }

    public function test_find_by_email_should_return_null(): void
    {
        $this->assertNull(User::findByEmail('not.exits@example.com'));
    }

    public function test_authenticate_should_return_the_true(): void
    {
        $this->assertTrue($this->user->authenticate('123456'));
        $this->assertFalse($this->user->authenticate('wrong'));
    }

    public function test_authenticate_should_return_false(): void
    {
        $this->assertFalse($this->user->authenticate(''));
    }

    public function test_errors_should_return_profile_error(): void
    {
        $user = new User([
            'profile' => 'admin',
            'name' => 'User 3',
            'email' => 'fulano3@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);

        $this->assertFalse($user->isValid());
        $this->assertFalse($user->save());

        $this->assertEquals('não é um valor válido!', $user->errors('profile'));
    }

    public function test_errors_should_return_email_error(): void
    {
        $user = new User([
            'profile' => User::PROFILE_STUDENT,
            'name' => 'User 3',
            'email' => 'fulano3.example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);

        $this->assertFalse($user->isValid());
        $this->assertFalse($user->save());

        $this->assertEquals('não é um e-mail válido!', $user->errors('email'));
    }

    public function test_errors_should_return_empty_password_error(): void
    {
        $user = new User([
            'profile' => User::PROFILE_STUDENT,
            'name' => 'User 3',
            'email' => 'fulano3@example.com',
            'password' => '',
            'password_confirmation' => ''
        ]);

        $this->assertFalse($user->isValid());
        $this->assertFalse($user->save());

        $this->assertEquals('não pode ser vazio!', $user->errors('password'));
    }

    public function test_profile_predicates(): void
    {
        $student = new User(['profile' => User::PROFILE_STUDENT]);
        $teacher = new User(['profile' => User::PROFILE_TEACHER]);
        $admin = new User(['profile' => User::PROFILE_ADMIN]);

        $this->assertTrue($student->isStudent());
        $this->assertFalse($student->isTeacher());
        $this->assertFalse($student->isAdmin());

        $this->assertTrue($teacher->isTeacher());
        $this->assertFalse($teacher->isStudent());
        $this->assertFalse($teacher->isAdmin());

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStudent());
        $this->assertFalse($admin->isTeacher());
    }

    public function test_has_profile(): void
    {
        $this->assertTrue($this->user->hasProfile(User::PROFILE_STUDENT));
        $this->assertTrue($this->user->hasProfile(User::PROFILE_TEACHER, User::PROFILE_STUDENT));
        $this->assertFalse($this->user->hasProfile(User::PROFILE_TEACHER, User::PROFILE_ADMIN));
        $this->assertFalse($this->user->hasProfile());
    }

    public function test_home_route_name(): void
    {
        $this->assertEquals('student.home', (new User(['profile' => User::PROFILE_STUDENT]))->homeRouteName());
        $this->assertEquals('teacher.home', (new User(['profile' => User::PROFILE_TEACHER]))->homeRouteName());
        $this->assertEquals('admin.home', (new User(['profile' => User::PROFILE_ADMIN]))->homeRouteName());
    }

    public function test_profile_label(): void
    {
        $this->assertEquals('Discente', (new User(['profile' => User::PROFILE_STUDENT]))->profileLabel());
        $this->assertEquals('Docente', (new User(['profile' => User::PROFILE_TEACHER]))->profileLabel());
        $this->assertEquals('Técnico (admin)', (new User(['profile' => User::PROFILE_ADMIN]))->profileLabel());
    }

    public function test_update_should_not_change_the_password(): void
    {
        $this->user->password = '654321';
        $this->user->save();

        $this->assertTrue($this->user->authenticate('123456'));
        $this->assertFalse($this->user->authenticate('654321'));
    }

    public function test_is_valid_for_signup_only_for_student_and_teacher(): void
    {
        $expectations = [User::PROFILE_STUDENT => true, User::PROFILE_TEACHER => true, User::PROFILE_ADMIN => false];

        foreach ($expectations as $profile => $expected) {
            $user = new User([
                'profile' => $profile,
                'name' => 'User 3',
                'email' => 'fulano3@example.com',
                'password' => '123456',
                'password_confirmation' => '123456'
            ]);

            $this->assertSame($expected, $user->isValidForSignup(), $profile);
        }

        $this->assertEquals('não é um valor válido!', $user->errors('profile'));
    }
}
