<?php

namespace Tests\Unit\Lib\Validations;

use PHPUnit\Framework\TestCase;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

class ValidationsTest extends TestCase
{
    public function test_not_empty(): void
    {
        $model = new class () extends Model {
            protected static array $columns = ['name'];
        };

        $this->assertFalse(Validations::notEmpty('name', $model));

        $model->name = 'Diego'; // @phpstan-ignore-line
        $this->assertTrue(Validations::notEmpty('name', $model));
    }

    public function test_password_confirmation(): void
    {
        $model = new class () extends Model {
            protected ?string $password = null;
            protected ?string $password_confirmation = null;
        };

        $model->password = '123456'; // @phpstan-ignore-line
        $model->password_confirmation = 'wrong'; // @phpstan-ignore-line

        $this->assertFalse(Validations::passwordConfirmation($model));

        $model->password_confirmation = '123456'; // @phpstan-ignore-line
        $this->assertTrue(Validations::passwordConfirmation($model));
    }

    public function test_email(): void
    {
        $model = new class () extends Model {
            protected static array $columns = ['email'];
        };

        $this->assertTrue(Validations::email('email', $model));

        $model->email = 'invalid-email'; // @phpstan-ignore-line
        $this->assertFalse(Validations::email('email', $model));
        $this->assertEquals('não é um e-mail válido!', $model->errors('email'));

        $model->email = 'fulano@example.com';
        $this->assertTrue(Validations::email('email', $model));
    }

    public function test_inclusion(): void
    {
        $model = new class () extends Model {
            protected static array $columns = ['profile'];
        };

        $this->assertFalse(Validations::inclusion('profile', ['a', 'b'], $model));

        $model->profile = 'c'; // @phpstan-ignore-line
        $this->assertFalse(Validations::inclusion('profile', ['a', 'b'], $model));
        $this->assertEquals('não é um valor válido!', $model->errors('profile'));

        $model->profile = 'a';
        $this->assertTrue(Validations::inclusion('profile', ['a', 'b'], $model));
    }

    public function test_min_length(): void
    {
        $model = new class () extends Model {
            protected static array $columns = ['password'];
        };

        $length = 6;

        $this->assertTrue(Validations::minLength('password', $length, $model));

        $model->password = "12345"; // @phpstan-ignore-line
        $this->assertFalse(Validations::minLength('password', $length, $model));
        $this->assertEquals("deve ter no mínimo {$length} caracteres!", $model->errors('password'));

        $model->password = "123456";
        $this->assertTrue(Validations::minLength('password', $length, $model));

        $model->password = "1234567";
        $this->assertTrue(Validations::minLength('password', $length, $model));
    }
}
