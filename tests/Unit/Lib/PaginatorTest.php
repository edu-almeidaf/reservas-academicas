<?php

namespace Tests\Unit\Lib;

use App\Models\ReservationCategory;
use Lib\Paginator;
use Tests\TestCase;

class PaginatorTest extends TestCase
{
    private Paginator $paginator;
    /** @var ReservationCategory[] $categories */
    private array $categories = [];

    public function setUp(): void
    {
        parent::setUp();

        for ($i = 0; $i < 10; $i++) {
            $category = new ReservationCategory(['name' => "Category $i"]);
            $category->save();
            $this->categories[] = $category;
        }
        $this->paginator = $this->newPaginator(page: 1, perPage: 5);
    }

    private function newPaginator(int $page, int $perPage): Paginator
    {
        return new Paginator(ReservationCategory::class, $page, $perPage, 'reservation_categories', ['name']);
    }

    public function test_total_of_registers(): void
    {
        $this->assertEquals(10, $this->paginator->totalOfRegisters());
    }

    public function test_total_of_pages(): void
    {
        $this->assertEquals(2, $this->paginator->totalOfPages());
    }

    public function test_total_of_pages_when_the_division_is_not_exact(): void
    {
        (new ReservationCategory(['name' => 'Category 10']))->save();

        $this->assertEquals(3, $this->newPaginator(page: 1, perPage: 5)->totalOfPages());
    }

    public function test_previous_page(): void
    {
        $this->assertEquals(0, $this->paginator->previousPage());
    }

    public function test_next_page(): void
    {
        $this->assertEquals(2, $this->paginator->nextPage());
    }

    public function test_has_previous_page(): void
    {
        $this->assertFalse($this->paginator->hasPreviousPage());
        $this->assertTrue($this->newPaginator(page: 2, perPage: 5)->hasPreviousPage());
    }

    public function test_has_next_page(): void
    {
        $this->assertTrue($this->paginator->hasNextPage());
        $this->assertFalse($this->newPaginator(page: 2, perPage: 5)->hasNextPage());
    }

    public function test_is_page(): void
    {
        $this->assertTrue($this->paginator->isPage(1));
        $this->assertFalse($this->paginator->isPage(2));
    }

    public function test_entries_info(): void
    {
        $this->assertEquals('Mostrando 1 - 5 de 10', $this->paginator->entriesInfo());
        $this->assertEquals('Mostrando 6 - 10 de 10', $this->newPaginator(page: 2, perPage: 5)->entriesInfo());
    }

    public function test_registers(): void
    {
        $this->assertCount(5, $this->paginator->registers());
        $this->assertEquals($this->categories, $this->newPaginator(page: 1, perPage: 10)->registers());
    }

    public function test_route_name(): void
    {
        $this->assertEquals('reservation_categories.paginate', $this->paginator->getRouteName());
    }
}
