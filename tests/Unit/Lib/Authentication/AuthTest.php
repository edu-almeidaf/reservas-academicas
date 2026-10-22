<?php

namespace Tests\Unit\Lib\Authentication;

use Lib\Authentication\Auth;
use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private User $user;

    public function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $this->user = new User([
            'profile' => 'discente',
            'name' => 'User 1',
            'email' => 'fulano@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $this->user->save();
    }

    public function tearDown(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    public function test_login(): void
    {
        Auth::login($this->user);

        $this->assertEquals(1, $_SESSION['user']['id']);
    }

    public function test_user(): void
    {
        Auth::login($this->user);

        $userFromSession = Auth::user();

        $this->assertEquals($this->user->id, $userFromSession->id);
    }

    public function test_check(): void
    {
        Auth::login($this->user);

        $this->assertTrue(Auth::check());
    }

    public function test_logout(): void
    {
        Auth::login($this->user);
        Auth::logout();

        $this->assertFalse(Auth::check());
    }

    public function test_start_code_verification_stores_an_eight_digit_code(): void
    {
        $code = Auth::startCodeVerification($this->user);

        $this->assertMatchesRegularExpression('/^\d{8}$/', $code);
        $this->assertTrue(Auth::hasPendingCode());
        $this->assertFalse(Auth::check());
    }

    public function test_has_pending_code_without_verification(): void
    {
        $this->assertFalse(Auth::hasPendingCode());
    }

    public function test_verify_code_returns_the_user_and_clears_the_code(): void
    {
        $code = Auth::startCodeVerification($this->user);

        $user = Auth::verifyCode($code);

        $this->assertNotNull($user);
        $this->assertEquals($this->user->id, $user->id);
        $this->assertFalse(Auth::hasPendingCode());
    }

    public function test_verify_code_with_wrong_code_returns_null(): void
    {
        Auth::startCodeVerification($this->user);

        $this->assertNull(Auth::verifyCode('00000000x'));
        $this->assertTrue(Auth::hasPendingCode());
    }

    public function test_verify_code_without_verification_returns_null(): void
    {
        $this->assertNull(Auth::verifyCode('12345678'));
    }
}
