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
    protected static string $table = 'users';
    protected static array $columns = ['profile', 'name', 'email', 'password_hash'];

    protected ?string $password = null;
    protected ?string $password_confirmation = null;

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::notEmpty('email', $this);

        Validations::uniqueness('email', $this);

        if ($this->newRecord()) {
            Validations::passwordConfirmation($this);
        }
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
