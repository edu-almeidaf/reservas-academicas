<?php

namespace Lib\Authentication;

use App\Models\User;

class Auth
{
    public const CODE_LENGTH = 8;

    public static function login(User $user): void
    {
        self::regenerateSession();
        $_SESSION['user']['id'] = $user->id;
    }

    public static function user(): ?User
    {
        if (isset($_SESSION['user']['id'])) {
            $id = $_SESSION['user']['id'];
            return User::findById($id);
        }

        return null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']) && self::user() !== null;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']['id']);
        self::regenerateSession();
    }

    public static function startCodeVerification(User $user): string
    {
        $code = str_pad((string) random_int(0, 10 ** self::CODE_LENGTH - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        $_SESSION['login_code'] = [
            'user_id' => $user->id,
            'code' => $code,
        ];

        return $code;
    }

    public static function hasPendingCode(): bool
    {
        return isset($_SESSION['login_code']['user_id'], $_SESSION['login_code']['code']);
    }

    public static function verifyCode(string $code): ?User
    {
        if (!self::hasPendingCode() || !hash_equals($_SESSION['login_code']['code'], $code)) {
            return null;
        }

        $user = User::findById($_SESSION['login_code']['user_id']);
        unset($_SESSION['login_code']);

        return $user;
    }

    private static function regenerateSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
