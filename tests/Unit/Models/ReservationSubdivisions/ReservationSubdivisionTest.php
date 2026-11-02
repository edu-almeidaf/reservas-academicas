<?php

namespace Tests\Unit\Models\ReservationSubdivisions;

use App\Models\Reservation;
use App\Models\ReservationSubdivision;
use App\Models\Subdivision;
use Core\Database\Database;
use PDOException;
use Tests\TestCase;
use Tests\Unit\Models\Reservations\ReservationFixtures;

class ReservationSubdivisionTest extends TestCase
{
    use ReservationFixtures;

    private Reservation $reservation;
    private Subdivision $subdivision;
    private ReservationSubdivision $link;

    public function setUp(): void
    {
        parent::setUp();
        $this->createReservationFixtures();

        $this->reservation = $this->newReservation();
        $this->reservation->save();

        $this->subdivision = new Subdivision(['resource_id' => $this->resource->id, 'name' => 'Bancada 1']);
        $this->subdivision->save();

        $this->link = $this->newLink();
        $this->link->save();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function newLink(array $overrides = []): ReservationSubdivision
    {
        return new ReservationSubdivision(array_merge([
            'reservation_id' => $this->reservation->id,
            'subdivision_id' => $this->subdivision->id,
        ], $overrides));
    }

    public function test_should_create_new_link(): void
    {
        $this->assertCount(1, ReservationSubdivision::all());
    }

    public function test_relations_should_return_both_sides(): void
    {
        $this->assertEquals($this->reservation->id, $this->link->reservation->id);
        $this->assertEquals($this->subdivision->id, $this->link->subdivision->id);
    }

    public function test_errors_should_return_empty_fields_errors(): void
    {
        $link = new ReservationSubdivision();

        $this->assertFalse($link->save());
        $this->assertEquals('não pode ser vazio!', $link->errors('reservation_id'));
        $this->assertEquals('não pode ser vazio!', $link->errors('subdivision_id'));
    }

    public function test_errors_should_return_nonexistent_reservation_and_subdivision_errors(): void
    {
        $link = $this->newLink(['reservation_id' => 999, 'subdivision_id' => 999]);

        $this->assertFalse($link->save());
        $this->assertEquals('não é um valor válido!', $link->errors('reservation_id'));
        $this->assertEquals('não é um valor válido!', $link->errors('subdivision_id'));
    }

    public function test_errors_should_return_duplicated_link_error(): void
    {
        $link = $this->newLink();

        $this->assertFalse($link->save());
        $this->assertEquals('já existe um registro com esse dado', $link->errors('subdivision_id'));
        $this->assertCount(1, ReservationSubdivision::all());
    }

    public function test_database_should_reject_duplicated_link(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionCode('23000');

        Database::getDatabaseConn()->exec(
            "INSERT INTO reservation_subdivision (reservation_id, subdivision_id)
             VALUES ({$this->reservation->id}, {$this->subdivision->id})"
        );
    }

    public function test_deleting_reservation_should_cascade_links(): void
    {
        $this->reservation->destroy();

        $this->assertSame([], ReservationSubdivision::where(['subdivision_id' => $this->subdivision->id]));
        $this->assertNotNull(Subdivision::findById($this->subdivision->id));
    }

    public function test_database_should_reject_deleting_linked_subdivision(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionCode('23000');

        $this->subdivision->destroy();
    }
}
