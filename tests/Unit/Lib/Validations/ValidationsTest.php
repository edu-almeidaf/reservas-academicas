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

    public function test_date(): void
    {
        $model = new class () extends Model {
            protected static array $columns = ['scheduled_date'];
        };

        $this->assertTrue(Validations::date('scheduled_date', $model));

        foreach (['2030-02-31', '31/12/2030', '2030-1-5', 'amanhã'] as $invalid) {
            $model->scheduled_date = $invalid; // @phpstan-ignore-line
            $this->assertFalse(Validations::date('scheduled_date', $model), $invalid);
            $this->assertEquals('não é uma data válida!', $model->errors('scheduled_date'));
        }

        $model->scheduled_date = '2030-12-31';
        $this->assertTrue(Validations::date('scheduled_date', $model));
    }

    public function test_time(): void
    {
        $model = new class () extends Model {
            protected static array $columns = ['start_time'];
        };

        $this->assertTrue(Validations::time('start_time', $model));

        foreach (['25:00', '8:00', '08:60', '8h'] as $invalid) {
            $model->start_time = $invalid; // @phpstan-ignore-line
            $this->assertFalse(Validations::time('start_time', $model), $invalid);
            $this->assertEquals('não é um horário válido!', $model->errors('start_time'));
        }

        $model->start_time = '08:30';
        $this->assertTrue(Validations::time('start_time', $model));
    }
}
