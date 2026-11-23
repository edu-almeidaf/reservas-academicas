<?php

namespace Tests\Unit\Models\Subdivisions;

use App\Models\Reservation;
use App\Models\ReservationSubdivision;
use App\Models\Resource;
use App\Models\Subdivision;
use Tests\TestCase;
use Tests\Unit\Models\Reservations\ReservationFixtures;

class SubdivisionTest extends TestCase
{
    use ReservationFixtures;

    private Subdivision $subdivision;

    public function setUp(): void
    {
        parent::setUp();
        $this->createReservationFixtures();

        $this->subdivision = $this->newSubdivision();
        $this->subdivision->save();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function newSubdivision(array $overrides = []): Subdivision
    {
        return new Subdivision(array_merge([
            'resource_id' => $this->resource->id,
            'name' => 'Bancada 1',
        ], $overrides));
    }

    public function test_should_create_new_active_subdivision(): void
    {
        $this->assertCount(1, Subdivision::all());
        $this->assertSame(1, $this->subdivision->active);
        $this->assertSame(1, Subdivision::findById($this->subdivision->id)->active);
    }

    public function test_active_given_in_the_params_is_kept(): void
    {
        $this->assertSame(0, $this->newSubdivision(['active' => 0])->active);
    }

    public function test_resource_should_return_the_subdivision_resource(): void
    {
        $this->assertInstanceOf(Resource::class, $this->subdivision->resource);
        $this->assertEquals($this->resource->id, $this->subdivision->resource->id);
    }

    public function test_errors_should_return_empty_fields_errors(): void
    {
        $subdivision = new Subdivision();

        $this->assertFalse($subdivision->save());
        $this->assertEquals('não pode ser vazio!', $subdivision->errors('resource_id'));
        $this->assertEquals('não pode ser vazio!', $subdivision->errors('name'));
    }

    public function test_errors_should_return_nonexistent_resource_error(): void
    {
        $subdivision = $this->newSubdivision(['resource_id' => 999]);

        $this->assertFalse($subdivision->save());
        $this->assertEquals('não é um valor válido!', $subdivision->errors('resource_id'));
    }

    public function test_errors_should_return_duplicated_name_in_the_same_resource_error(): void
    {
        $subdivision = $this->newSubdivision();

        $this->assertFalse($subdivision->save());
        $this->assertEquals('já existe um registro com esse dado', $subdivision->errors('name'));
    }

    public function test_same_name_in_another_resource_is_valid(): void
    {
        $otherResource = new Resource([
            'resource_category_id' => $this->resource->resource_category_id,
            'name' => 'Laboratório 2',
            'active' => 1,
        ]);
        $otherResource->save();

        $this->assertTrue($this->newSubdivision(['resource_id' => $otherResource->id])->save());
    }

    public function test_update_should_keep_the_same_name(): void
    {
        $this->subdivision->active = 0;

        $this->assertTrue($this->subdivision->save());
    }

    public function test_errors_should_return_invalid_active_error(): void
    {
        $subdivision = $this->newSubdivision(['name' => 'Bancada 2', 'active' => 2]);

        $this->assertFalse($subdivision->save());
        $this->assertEquals('não é um valor válido!', $subdivision->errors('active'));
    }

    public function test_is_active_and_status_label(): void
    {
        $inactive = $this->newSubdivision(['active' => 0]);

        $this->assertTrue($this->subdivision->isActive());
        $this->assertSame('Ativa', $this->subdivision->statusLabel());
        $this->assertFalse($inactive->isActive());
        $this->assertSame('Inativa', $inactive->statusLabel());
    }

    public function test_reservations_should_return_the_linked_reservations(): void
    {
        $linked = $this->newReservation();
        $linked->save();
        $notLinked = $this->newReservation(['start_time' => '10:00', 'end_time' => '11:00']);
        $notLinked->save();

        $link = new ReservationSubdivision(['reservation_id' => $linked->id, 'subdivision_id' => $this->subdivision->id]);
        $link->save();

        $reservations = $this->subdivision->reservations()->get();

        $this->assertCount(1, $reservations);
        $this->assertInstanceOf(Reservation::class, $reservations[0]);
        $this->assertEquals($linked->id, $reservations[0]->id);
        $this->assertSame(1, $this->subdivision->reservations()->count());
    }

    public function test_has_reservations(): void
    {
        $this->assertFalse($this->subdivision->hasReservations());

        $reservation = $this->newReservation();
        $reservation->save();
        $link = new ReservationSubdivision([
            'reservation_id' => $reservation->id,
            'subdivision_id' => $this->subdivision->id,
        ]);
        $link->save();

        $this->assertTrue($this->subdivision->hasReservations());
    }
}
