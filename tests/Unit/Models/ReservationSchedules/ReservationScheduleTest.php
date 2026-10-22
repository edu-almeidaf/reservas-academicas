<?php

namespace Tests\Unit\Models\ReservationSchedules;

use App\Models\Reservation;
use App\Models\ReservationSchedule;
use App\Models\Resource;
use Tests\TestCase;
use Tests\Unit\Models\Reservations\ReservationFixtures;

class ReservationScheduleTest extends TestCase
{
    use ReservationFixtures;

    private Reservation $reservation;

    public function setUp(): void
    {
        parent::setUp();
        $this->createReservationFixtures();

        $this->reservation = $this->newReservation(['start_time' => '08:00', 'end_time' => '10:00']);
        $this->reservation->save();
    }

    private function hasConflict(string $start, string $end, ?int $resourceId = null, ?string $date = null): bool
    {
        return ReservationSchedule::hasConflict(
            $resourceId ?? $this->resource->id,
            $date ?? $this->futureDate(),
            $start,
            $end
        );
    }

    public function test_has_conflict_with_partial_and_total_overlap(): void
    {
        $this->assertTrue($this->hasConflict('09:00', '11:00'));
        $this->assertTrue($this->hasConflict('07:00', '09:00'));
        $this->assertTrue($this->hasConflict('08:30', '09:30'));
        $this->assertTrue($this->hasConflict('07:00', '11:00'));
    }

    public function test_adjacent_schedules_do_not_conflict(): void
    {
        $this->assertFalse($this->hasConflict('10:00', '12:00'));
        $this->assertFalse($this->hasConflict('06:00', '08:00'));
    }

    public function test_other_resource_or_date_do_not_conflict(): void
    {
        $other = new Resource([
            'resource_category_id' => $this->resource->resource_category_id,
            'name' => 'Laboratório 2',
            'active' => 1,
        ]);
        $other->save();

        $this->assertFalse($this->hasConflict('08:00', '10:00', $other->id));
        $this->assertFalse($this->hasConflict('08:00', '10:00', null, $this->futureDate(2)));
    }

    public function test_rejected_and_cancelled_reservations_do_not_conflict(): void
    {
        foreach ([Reservation::STATUS_REJECTED, Reservation::STATUS_CANCELLED] as $status) {
            $this->reservation->status = $status;
            $this->reservation->save();

            $this->assertFalse($this->hasConflict('08:00', '10:00'), $status);
        }
    }

    public function test_approved_reservations_conflict(): void
    {
        $this->reservation->status = Reservation::STATUS_APPROVED;
        $this->reservation->save();

        $this->assertTrue($this->hasConflict('08:00', '10:00'));
    }

    public function test_the_reservation_itself_is_ignored(): void
    {
        $this->assertFalse(ReservationSchedule::hasConflict(
            $this->resource->id,
            $this->futureDate(),
            '08:00',
            '10:00',
            $this->reservation->id
        ));
    }

    public function test_for_reservations_returns_the_schedules_by_reservation_id(): void
    {
        $other = $this->newReservation(['scheduled_date' => $this->futureDate(3)]);
        $other->save();

        $schedules = ReservationSchedule::forReservations([$this->reservation->id, $other->id]);

        $this->assertCount(2, $schedules);
        $this->assertEquals($this->futureDate(), $schedules[$this->reservation->id]->scheduled_date);
        $this->assertEquals($this->futureDate(3), $schedules[$other->id]->scheduled_date);
        $this->assertSame([], ReservationSchedule::forReservations([]));
    }
}
