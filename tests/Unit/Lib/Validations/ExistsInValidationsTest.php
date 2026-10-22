<?php

namespace Tests\Unit\Lib\Validations;

use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use Lib\Validations;
use PHPUnit\Framework\TestCase;

class ExistsInValidationsTest extends TestCase
{
    public function setUp(): void
    {
        Database::drop();
        Database::create();
        Database::exec('
            CREATE TABLE test_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL
            );
            INSERT INTO test_categories (name) VALUES ("Category 1");
        ');
    }

    public function tearDown(): void
    {
        Database::drop();
    }

    public function test_exists_in_ignores_empty_value(): void
    {
        $model = $this->newModel();

        $this->assertTrue(Validations::existsIn('category_id', $this->categoryClass(), $model));
        $this->assertFalse($model->hasErrors());
    }

    public function test_exists_in_with_existing_register(): void
    {
        $model = $this->newModel();
        $model->category_id = 1; // @phpstan-ignore-line

        $this->assertTrue(Validations::existsIn('category_id', $this->categoryClass(), $model));
    }

    public function test_exists_in_with_nonexistent_register(): void
    {
        $model = $this->newModel();
        $model->category_id = 99; // @phpstan-ignore-line

        $this->assertFalse(Validations::existsIn('category_id', $this->categoryClass(), $model));
        $this->assertEquals('não é um valor válido!', $model->errors('category_id'));
    }

    public function test_exists_in_with_non_integer_value(): void
    {
        $model = $this->newModel();
        $model->category_id = '1'; // @phpstan-ignore-line

        $this->assertFalse(Validations::existsIn('category_id', $this->categoryClass(), $model));
        $this->assertEquals('não é um valor válido!', $model->errors('category_id'));
    }

    private function newModel(): Model
    {
        return new class () extends Model {
            protected static array $columns = ['category_id'];
        };
    }

    /**
     * @return class-string<Model>
     */
    private function categoryClass(): string
    {
        $category = new class () extends Model {
            protected static string $table = 'test_categories';
            protected static array $columns = ['name'];
        };

        return $category::class;
    }
}
