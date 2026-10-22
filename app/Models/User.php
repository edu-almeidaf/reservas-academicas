<?php

namespace App\Models;

use DateTime;
use DateTimeZone;
use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property string $profile
 * @property string $name
 * @property string $email
 * @property string $password_hash
 * @property string|null $last_login_at
 * @property int $failed_login_attempts
 * @property string|null $locked_until
 */
class User extends Model
{
    public const PROFILE_STUDENT = 'discente';
    public const PROFILE_TEACHER = 'docente';
    public const PROFILE_ADMIN = 'tecnico';
    public const PROFILES = [self::PROFILE_STUDENT, self::PROFILE_TEACHER, self::PROFILE_ADMIN];
    public const SIGNUP_PROFILES = [self::PROFILE_STUDENT, self::PROFILE_TEACHER];
    public const PASSWORD_MIN_LENGTH = 6;
    public const TIMEZONE = 'America/Sao_Paulo';
    public const MAX_LOGIN_ATTEMPTS = 3;
    public const LOCK_MINUTES = 5;

    private const HOME_ROUTES = [
        self::PROFILE_STUDENT => 'student.home',
        self::PROFILE_TEACHER => 'teacher.home',
        self::PROFILE_ADMIN => 'admin.home',
    ];

    private const LABELS = [
        self::PROFILE_STUDENT => 'Discente',
        self::PROFILE_TEACHER => 'Docente',
        self::PROFILE_ADMIN => 'Técnico (admin)',
    ];

    protected static string $table = 'users';
    protected static array $columns = [
        'profile',
        'name',
        'email',
        'password_hash',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
    ];

    protected ?string $password = null;
    protected ?string $password_confirmation = null;

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::notEmpty('email', $this);
        Validations::email('email', $this);

        Validations::uniqueness('email', $this);
        Validations::inclusion('profile', self::PROFILES, $this);

        if ($this->newRecord()) {
            Validations::notEmpty('password', $this);
            Validations::passwordConfirmation($this);
            Validations::minLength('password', self::PASSWORD_MIN_LENGTH, $this);
        }
    }

    public function isValidForSignup(): bool
    {
        $valid = $this->isValid();

        if (!in_array($this->profile, self::SIGNUP_PROFILES, true)) {
            $this->addError('profile', 'não é um valor válido!');
            return false;
        }

        return $valid;
    }

    public function isStudent(): bool
    {
        return $this->hasProfile(self::PROFILE_STUDENT);
    }

    public function isTeacher(): bool
    {
        return $this->hasProfile(self::PROFILE_TEACHER);
    }

    public function isAdmin(): bool
    {
        return $this->hasProfile(self::PROFILE_ADMIN);
    }

    public function hasProfile(string ...$profiles): bool
    {
        return in_array($this->profile, $profiles, true);
    }

    public function homeRouteName(): string
    {
        return self::HOME_ROUTES[$this->profile];
    }

    public function profileLabel(): string
    {
        return self::LABELS[$this->profile];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        if ($this->failed_login_attempts === null) {
            $this->failed_login_attempts = 0;
        }
    }

    public function registerLogin(): void
    {
        $this->update([
            'last_login_at' => self::now()->format('Y-m-d H:i:s'),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    public function registerFailedLogin(): void
    {
        $attempts = $this->failed_login_attempts + 1;

        if ($attempts < self::MAX_LOGIN_ATTEMPTS) {
            $this->update(['failed_login_attempts' => $attempts]);
            return;
        }

        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => self::now()->modify('+' . self::LOCK_MINUTES . ' minutes')->format('Y-m-d H:i:s'),
        ]);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until > self::now()->format('Y-m-d H:i:s');
    }

    private static function now(): DateTime
    {
        return new DateTime('now', new DateTimeZone(self::TIMEZONE));
    }

    public function lastLoginLabel(): ?string
    {
        if ($this->last_login_at === null) {
            return null;
        }

        return (new DateTime($this->last_login_at))->format('d/m/Y \à\s H:i');
    }

    public function authenticate(string $password): bool
    {
        if ($this->password_hash == null) {
            return false;
        }

        return password_verify($password, $this->password_hash);
    }

    public static function findByEmail(string $email): User | null
    {
        return User::findBy(['email' => $email]);
    }

    public function __set(string $property, mixed $value): void
    {
        parent::__set($property, $value);

        if (
            $property === 'password' &&
            $this->newRecord() &&
            $value !== null && $value !== ''
        ) {
            $this->password_hash = password_hash($value, PASSWORD_DEFAULT);
        }
    }
}
