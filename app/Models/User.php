<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property string $profile
 * @property string $name
 * @property string $email
 * @property string $password_hash
 */
class User extends Model
{
    public const PROFILE_STUDENT = 'discente';
    public const PROFILE_TEACHER = 'docente';
    public const PROFILE_ADMIN = 'tecnico';
    public const PROFILES = [self::PROFILE_STUDENT, self::PROFILE_TEACHER, self::PROFILE_ADMIN];
    public const SIGNUP_PROFILES = [self::PROFILE_STUDENT, self::PROFILE_TEACHER];

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
    protected static array $columns = ['profile', 'name', 'email', 'password_hash'];

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
