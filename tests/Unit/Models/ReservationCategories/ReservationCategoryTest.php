<?php

namespace Tests\Unit\Models\ReservationCategories;

use App\Models\ReservationCategory;
use Tests\TestCase;

class ReservationCategoryTest extends TestCase
{
    public function test_should_create_new_category(): void
    {
        $category = new ReservationCategory(['name' => 'Aula']);

        $this->assertTrue($category->save());
        $this->assertCount(1, ReservationCategory::all());
    }

    public function test_errors_should_return_empty_name_error(): void
    {
        $category = new ReservationCategory(['name' => '']);

        $this->assertFalse($category->save());
        $this->assertEquals('não pode ser vazio!', $category->errors('name'));
    }

    public function test_errors_should_return_duplicated_name_error(): void
    {
        (new ReservationCategory(['name' => 'Aula']))->save();
        $category = new ReservationCategory(['name' => 'Aula']);

        $this->assertFalse($category->save());
        $this->assertEquals('já existe um registro com esse dado', $category->errors('name'));
    }

    public function test_options_should_return_names_by_id_ordered_by_name(): void
    {
        $prova = new ReservationCategory(['name' => 'Prova']);
        $prova->save();
        $aula = new ReservationCategory(['name' => 'Aula']);
        $aula->save();

        $this->assertSame([$aula->id => 'Aula', $prova->id => 'Prova'], ReservationCategory::options());
    }
}
