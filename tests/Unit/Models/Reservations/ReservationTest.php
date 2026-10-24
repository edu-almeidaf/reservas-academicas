<?php

namespace Tests\Unit\Models\Reservations;

use App\Models\Reservation;
use App\Models\ReservationSchedule;
use App\Models\Resource;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use ReservationFixtures;

    public function setUp(): void
    {
        parent::setUp();
        $this->createReservationFixtures();
    }

    public function test_should_create_a_pending_reservation_with_its_schedule(): void
    {
        $reservation = $this->newReservation();

        $this->assertTrue($reservation->save());
        $this->assertCount(1, Reservation::all());
        $this->assertEquals(Reservation::STATUS_PENDING, Reservation::findById($reservation->id)->status);

        $schedules = ReservationSchedule::where(['reservation_id' => $reservation->id]);
        $this->assertCount(1, $schedules);
        $this->assertEquals($this->futureDate(), $schedules[0]->scheduled_date);
        $this->assertEquals('08:00:00', $schedules[0]->start_time);
        $this->assertEquals('10:00:00', $schedules[0]->end_time);
    }

    public function test_status_given_in_the_params_is_kept(): void
    {
        $reservation = $this->newReservation(['status' => Reservation::STATUS_APPROVED]);

        $this->assertEquals(Reservation::STATUS_APPROVED, $reservation->status);
    }

    public function test_relations(): void
    {
        $reservation = $this->newReservation();
        $reservation->save();

        $this->assertEquals($this->user->id, $reservation->user->id);
        $this->assertEquals($this->resource->id, $reservation->resource->id);
        $this->assertEquals($this->reservationCategory->id, $reservation->category->id);
    }

    public function test_is_pending_and_status_label(): void
    {
        $pending = $this->newReservation();
        $approved = $this->newReservation(['status' => Reservation::STATUS_APPROVED]);

        $this->assertTrue($pending->isPending());
        $this->assertEquals('Pendente', $pending->statusLabel());
        $this->assertFalse($approved->isPending());
        $this->assertEquals('Deferida', $approved->statusLabel());
    }

    public function test_load_schedule_should_fill_the_schedule_fields(): void
    {
        $saved = $this->newReservation();
        $saved->save();

        $reservation = Reservation::findById($saved->id);
        $this->assertNull($reservation->scheduled_date);

        $reservation->loadSchedule();

        $this->assertEquals($this->futureDate(), $reservation->scheduled_date);
        $this->assertEquals('08:00', $reservation->start_time);
        $this->assertEquals('10:00', $reservation->end_time);
    }

    public function test_errors_should_return_empty_fields_errors(): void
    {
        $reservation = new Reservation(['user_id' => $this->user->id]);

        $this->assertFalse($reservation->save());
        foreach (['resource_id', 'reservation_category_id', 'motive', 'scheduled_date', 'start_time', 'end_time'] as $field) {
            $this->assertEquals('não pode ser vazio!', $reservation->errors($field), $field);
        }
        $this->assertCount(0, Reservation::all());
    }

    public function test_errors_should_return_nonexistent_resource_and_category_errors(): void
    {
        $reservation = $this->newReservation(['resource_id' => 999, 'reservation_category_id' => 999]);

        $this->assertFalse($reservation->save());
        $this->assertEquals('não é um valor válido!', $reservation->errors('resource_id'));
        $this->assertEquals('não é um valor válido!', $reservation->errors('reservation_category_id'));
    }

    public function test_errors_should_return_inactive_resource_error(): void
    {
        $inactive = new Resource([
            'resource_category_id' => $this->resource->resource_category_id,
            'name' => 'Laboratório Inativo',
            'active' => 0,
        ]);
        $inactive->save();

        $reservation = $this->newReservation(['resource_id' => $inactive->id]);

        $this->assertFalse($reservation->save());
        $this->assertEquals('não está disponível para reservas!', $reservation->errors('resource_id'));
    }

    public function test_errors_should_return_invalid_date_error(): void
    {
        $reservation = $this->newReservation(['scheduled_date' => '2030-02-31']);

        $this->assertFalse($reservation->save());
        $this->assertEquals('não é uma data válida!', $reservation->errors('scheduled_date'));
    }

    public function test_errors_should_return_past_date_error(): void
    {
        $reservation = $this->newReservation(['scheduled_date' => date('Y-m-d', strtotime('-1 day'))]);

        $this->assertFalse($reservation->save());
        $this->assertEquals('não pode ser no passado!', $reservation->errors('scheduled_date'));
    }

    public function test_reservation_for_today_is_valid(): void
    {
        $this->assertTrue($this->newReservation(['scheduled_date' => date('Y-m-d')])->isValid());
    }

    public function test_errors_should_return_invalid_time_errors(): void
    {
        $reservation = $this->newReservation(['start_time' => '25:00', 'end_time' => '8h']);

        $this->assertFalse($reservation->save());
        $this->assertEquals('não é um horário válido!', $reservation->errors('start_time'));
        $this->assertEquals('não é um horário válido!', $reservation->errors('end_time'));
    }

    public function test_errors_should_keep_empty_error_when_only_one_time_is_given(): void
    {
        $reservation = $this->newReservation(['end_time' => '']);

        $this->assertFalse($reservation->save());
        $this->assertEquals('não pode ser vazio!', $reservation->errors('end_time'));
    }

    public function test_errors_should_return_end_before_start_error(): void
    {
        $reservation = $this->newReservation(['start_time' => '10:00', 'end_time' => '10:00']);

        $this->assertFalse($reservation->save());
        $this->assertEquals('deve ser depois do horário de início!', $reservation->errors('end_time'));
    }

    public function test_errors_should_return_conflict_error(): void
    {
        $this->newReservation()->save();
        $reservation = $this->newReservation(['start_time' => '09:00', 'end_time' => '11:00']);

        $this->assertFalse($reservation->save());
        $this->assertEquals('Horário indisponível para reserva.', $reservation->errors('start_time'));
        $this->assertCount(1, Reservation::all());
    }

    public function test_errors_should_return_invalid_status_error(): void
    {
        $reservation = $this->newReservation(['status' => 'aprovada']);

        $this->assertFalse($reservation->save());
        $this->assertEquals('não é um valor válido!', $reservation->errors('status'));
    }

    public function test_update_should_keep_a_single_schedule(): void
    {
        $reservation = $this->newReservation();
        $reservation->save();

        $reservation->start_time = '09:00';
        $reservation->end_time = '11:00';

        $this->assertTrue($reservation->save());
        $schedules = ReservationSchedule::where(['reservation_id' => $reservation->id]);
        $this->assertCount(1, $schedules);
        $this->assertEquals('09:00:00', $schedules[0]->start_time);
    }

    public function test_update_without_changing_the_schedule_does_not_conflict_with_itself(): void
    {
        $reservation = $this->newReservation();
        $reservation->save();

        $reservation->motive = 'Outro motivo';

        $this->assertTrue($reservation->save());
    }

    public function test_destroy_should_remove_the_schedule(): void
    {
        $reservation = $this->newReservation();
        $reservation->save();

        $reservation->destroy();

        $this->assertCount(0, Reservation::all());
        $this->assertCount(0, ReservationSchedule::all());
    }
}
