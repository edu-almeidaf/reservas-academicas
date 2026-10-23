<?php

namespace Tests\Integration\Controllers;

use App\Models\Reservation;
use App\Models\ReservationSchedule;
use App\Models\Resource;
use Core\Exceptions\HTTPException;
use Tests\Unit\Models\Reservations\ReservationFixtures;

class ReservationsControllerTest extends ControllerTestCase
{
    use ReservationFixtures;

    public function setUp(): void
    {
        parent::setUp();
        $this->createReservationFixtures();
        $_SESSION['user']['id'] = $this->user->id;
    }

    private function createReservation(int $day, string $start = '08:00', string $end = '10:00'): Reservation
    {
        $reservation = $this->newReservation([
            'scheduled_date' => $this->futureDate($day),
            'start_time' => $start,
            'end_time' => $end,
        ]);
        $reservation->save();

        return $reservation;
    }

    public function test_list_only_the_user_reservations(): void
    {
        $reservation = $this->createReservation(1);
        $other = $this->newReservation([
            'user_id' => $this->createUser('outro@example.com')->id,
            'scheduled_date' => $this->futureDate(2),
        ]);
        $other->save();

        $response = $this->get(action: 'index', controllerName: 'App\Controllers\ReservationsController');

        $this->assertMatchesRegularExpression('/<h1>\s*Minhas Reservas\s*<\/h1>/', $response);
        $this->assertStringContainsString("href=\"/reservations/{$reservation->id}\">#{$reservation->id}</a>", $response);
        $this->assertStringNotContainsString("href=\"/reservations/{$other->id}\"", $response);
        $this->assertStringContainsString($this->resource->name, $response);
        $this->assertStringContainsString(date('d/m/Y', strtotime($this->futureDate(1))), $response);
        $this->assertStringContainsString('08:00 - 10:00', $response);
        $this->assertStringContainsString('Pendente', $response);
        $this->assertStringContainsString('Mostrando 1 - 1 de 1', $response);
    }

    public function test_list_the_second_page(): void
    {
        for ($day = 1; $day <= 12; $day++) {
            $this->createReservation($day);
        }

        $response = $this->get(
            action: 'index',
            controllerName: 'App\Controllers\ReservationsController',
            params: ['page' => '2']
        );

        $this->assertStringContainsString('Mostrando 11 - 12 de 12', $response);
        $this->assertEquals(2, substr_count($response, '08:00 - 10:00'));
    }

    public function test_list_without_reservations(): void
    {
        $response = $this->get(action: 'index', controllerName: 'App\Controllers\ReservationsController');

        $this->assertStringContainsString('Você ainda não tem reservas.', $response);
        $this->assertStringNotContainsString('<table', $response);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidPageProvider(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-1'],
            'not a number' => ['abc'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPageProvider')]
    public function test_invalid_page_is_not_found(string $page): void
    {
        try {
            $this->get(
                action: 'index',
                controllerName: 'App\Controllers\ReservationsController',
                params: ['page' => $page]
            );
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Página não encontrada', $e->getMessage());
        }
    }

    public function test_show_reservation(): void
    {
        $reservation = $this->createReservation(1);

        $response = $this->get(
            action: 'show',
            controllerName: 'App\Controllers\ReservationsController',
            params: ['id' => (string) $reservation->id]
        );

        $this->assertMatchesRegularExpression("/<h1>\s*Reserva #{$reservation->id}\s*<\/h1>/", $response);
        $this->assertStringContainsString($this->resource->name, $response);
        $this->assertStringContainsString($this->reservationCategory->name, $response);
        $this->assertStringContainsString(date('d/m/Y', strtotime($this->futureDate(1))), $response);
        $this->assertStringContainsString('08:00 - 10:00', $response);
        $this->assertStringContainsString('Aula de Banco de Dados', $response);
        $this->assertStringContainsString('Pendente', $response);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function notFoundReservationProvider(): array
    {
        return [
            'nonexistent' => ['999'],
            'not a number' => ['abc'],
            'from another user' => ['other'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('notFoundReservationProvider')]
    public function test_show_reservation_not_found(string $id): void
    {
        if ($id === 'other') {
            $other = $this->newReservation(['user_id' => $this->createUser('outro@example.com')->id]);
            $other->save();
            $id = (string) $other->id;
        }

        try {
            $this->get(
                action: 'show',
                controllerName: 'App\Controllers\ReservationsController',
                params: ['id' => $id]
            );
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Reserva não encontrada', $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function reservationParams(array $overrides = []): array
    {
        return ['reservation' => array_merge([
            'resource_id' => (string) $this->resource->id,
            'reservation_category_id' => (string) $this->reservationCategory->id,
            'scheduled_date' => $this->futureDate(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'motive' => 'Aula de Banco de Dados',
        ], $overrides)];
    }

    public function test_render_new_reservation_form(): void
    {
        $inactive = new Resource([
            'resource_category_id' => $this->resource->resource_category_id,
            'name' => 'Laboratório Inativo',
            'active' => 0,
        ]);
        $inactive->save();

        $response = $this->get(action: 'new', controllerName: 'App\Controllers\ReservationsController');

        $this->assertMatchesRegularExpression('/<h1>\s*Nova Reserva\s*<\/h1>/', $response);
        $this->assertStringContainsString('<form action="/reservations" method="POST"', $response);
        foreach (['resource_id', 'reservation_category_id', 'scheduled_date', 'start_time', 'end_time', 'motive'] as $field) {
            $this->assertStringContainsString("name=\"reservation[{$field}]\"", $response);
        }
        $this->assertStringContainsString($this->resource->name, $response);
        $this->assertStringNotContainsString('Laboratório Inativo', $response);
        $this->assertStringContainsString($this->reservationCategory->name, $response);
        $this->assertStringContainsString('Solicitar', $response);
    }

    public function test_successfully_create_reservation(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\ReservationsController',
            params: $this->reservationParams()
        );

        $this->assertEquals('Location: /reservations', $response);
        $this->assertEquals('Reserva solicitada com sucesso!', $_SESSION['flash']['success']);

        $reservations = Reservation::all();
        $this->assertCount(1, $reservations);
        $this->assertEquals($this->user->id, $reservations[0]->user_id);
        $this->assertEquals(Reservation::STATUS_PENDING, $reservations[0]->status);
        $this->assertCount(1, ReservationSchedule::where(['reservation_id' => $reservations[0]->id]));
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string, 2: string}>
     */
    public static function invalidReservationProvider(): array
    {
        return [
            'empty motive' => [['motive' => ''], 'motive', 'não pode ser vazio!'],
            'past date' => [['scheduled_date' => '2020-01-01'], 'scheduled_date', 'não pode ser no passado!'],
            'end before start' => [['end_time' => '07:00'], 'end_time', 'deve ser depois do horário de início!'],
            'nonexistent resource' => [['resource_id' => '999'], 'resource_id', 'não é um valor válido!'],
            'resource not a number' => [['resource_id' => 'abc'], 'resource_id', 'não pode ser vazio!'],
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidReservationProvider')]
    public function test_unsuccessfully_create_reservation(array $overrides, string $field, string $message): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\ReservationsController',
            params: $this->reservationParams($overrides)
        );

        $this->assertStringContainsString('Existem dados incorretos! Por favor, verifique!', $response);
        $this->assertMatchesRegularExpression("/name=\"reservation\[{$field}\]\"[^>]*is-invalid/s", $response);
        $this->assertStringContainsString($message, $response);
        $this->assertCount(0, Reservation::all());
    }

    public function test_unsuccessfully_create_reservation_with_schedule_conflict(): void
    {
        $this->createReservation(1);

        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\ReservationsController',
            params: $this->reservationParams(['start_time' => '09:00', 'end_time' => '11:00'])
        );

        $this->assertStringContainsString('Horário indisponível para reserva.', $response);
        $this->assertCount(1, Reservation::all());
    }

    public function test_unsuccessfully_create_reservation_with_malformed_payload(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\ReservationsController',
            params: ['reservation' => 'texto']
        );

        $this->assertStringContainsString('Existem dados incorretos! Por favor, verifique!', $response);
        $this->assertCount(0, Reservation::all());
    }

    public function test_forged_fields_are_ignored(): void
    {
        $otherUser = $this->createUser('outro@example.com');

        $response = $this->post(
            action: 'create',
            controllerName: 'App\Controllers\ReservationsController',
            params: $this->reservationParams([
                'user_id' => (string) $otherUser->id,
                'status' => Reservation::STATUS_APPROVED,
                'id' => '50',
            ])
        );

        $this->assertEquals('Location: /reservations', $response);
        $reservation = Reservation::all()[0];
        $this->assertEquals($this->user->id, $reservation->user_id);
        $this->assertEquals(Reservation::STATUS_PENDING, $reservation->status);
        $this->assertNotEquals(50, $reservation->id);
    }

    public function test_render_edit_form_with_the_reservation_values(): void
    {
        $reservation = $this->createReservation(1);

        $response = $this->get(
            action: 'edit',
            controllerName: 'App\Controllers\ReservationsController',
            params: ['id' => (string) $reservation->id]
        );

        $this->assertMatchesRegularExpression("/<h1>\s*Editar Reserva #{$reservation->id}\s*<\/h1>/", $response);
        $this->assertStringContainsString("<form action=\"/reservations/{$reservation->id}\" method=\"POST\"", $response);
        $this->assertStringContainsString('<input type="hidden" name="_method" value="PUT">', $response);
        $this->assertStringContainsString("value=\"{$this->futureDate(1)}\"", $response);
        $this->assertStringContainsString('value="08:00"', $response);
        $this->assertStringContainsString('value="10:00"', $response);
        $this->assertStringContainsString('Aula de Banco de Dados</textarea>', $response);
        $this->assertStringContainsString('Atualizar', $response);
    }

    public function test_successfully_update_reservation(): void
    {
        $reservation = $this->createReservation(1);

        $response = $this->put(
            action: 'update',
            controllerName: 'App\Controllers\ReservationsController',
            params: ['id' => (string) $reservation->id] + $this->reservationParams([
                'scheduled_date' => $this->futureDate(1),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'motive' => 'Aula de Redes',
            ])
        );

        $this->assertEquals('Location: /reservations', $response);
        $this->assertEquals('Reserva atualizada com sucesso!', $_SESSION['flash']['success']);

        $updated = Reservation::findById($reservation->id);
        $updated->loadSchedule();
        $this->assertEquals('Aula de Redes', $updated->motive);
        $this->assertEquals('14:00', $updated->start_time);
        $this->assertEquals('16:00', $updated->end_time);
        $this->assertCount(1, ReservationSchedule::where(['reservation_id' => $reservation->id]));
    }

    public function test_update_keeping_the_schedule_does_not_conflict_with_itself(): void
    {
        $reservation = $this->createReservation(1);

        $response = $this->put(
            action: 'update',
            controllerName: 'App\Controllers\ReservationsController',
            params: ['id' => (string) $reservation->id, 'reservation' => ['motive' => 'Outro motivo']]
        );

        $this->assertEquals('Location: /reservations', $response);
        $this->assertEquals('Outro motivo', Reservation::findById($reservation->id)->motive);
    }

    public function test_unsuccessfully_update_reservation(): void
    {
        $reservation = $this->createReservation(1);

        $response = $this->put(
            action: 'update',
            controllerName: 'App\Controllers\ReservationsController',
            params: ['id' => (string) $reservation->id, 'reservation' => ['motive' => '', 'end_time' => '07:00']]
        );

        $this->assertStringContainsString('Existem dados incorretos! Por favor, verifique!', $response);
        $this->assertStringContainsString('não pode ser vazio!', $response);
        $this->assertStringContainsString('deve ser depois do horário de início!', $response);

        $unchanged = Reservation::findById($reservation->id);
        $unchanged->loadSchedule();
        $this->assertEquals('Aula de Banco de Dados', $unchanged->motive);
        $this->assertEquals('10:00', $unchanged->end_time);
    }

    public function test_unsuccessfully_update_reservation_with_schedule_conflict(): void
    {
        $this->createReservation(1, '14:00', '16:00');
        $reservation = $this->createReservation(1);

        $response = $this->put(
            action: 'update',
            controllerName: 'App\Controllers\ReservationsController',
            params: ['id' => (string) $reservation->id, 'reservation' => ['start_time' => '15:00', 'end_time' => '17:00']]
        );

        $this->assertStringContainsString('Horário indisponível para reserva.', $response);
    }

    public function test_not_pending_reservation_cannot_be_changed(): void
    {
        $reservation = $this->createReservation(1);
        $reservation->status = Reservation::STATUS_APPROVED;
        $reservation->save();
        $id = (string) $reservation->id;

        foreach (['edit' => 'get', 'update' => 'put'] as $action => $method) {
            unset($_SESSION['flash']);

            $response = $this->$method(
                action: $action,
                controllerName: 'App\Controllers\ReservationsController',
                params: ['id' => $id, 'reservation' => ['motive' => 'Outro motivo']]
            );

            $this->assertEquals("Location: /reservations/{$id}", $response, $action);
            $this->assertEquals('Somente reservas pendentes podem ser alteradas.', $_SESSION['flash']['danger']);
        }

        $this->assertEquals('Aula de Banco de Dados', Reservation::findById($reservation->id)->motive);
    }

    public function test_edit_link_only_for_pending_reservations(): void
    {
        $pending = $this->createReservation(1);
        $approved = $this->createReservation(2);
        $approved->status = Reservation::STATUS_APPROVED;
        $approved->save();

        $response = $this->get(action: 'index', controllerName: 'App\Controllers\ReservationsController');

        $this->assertStringContainsString("/reservations/{$pending->id}/edit", $response);
        $this->assertStringNotContainsString("/reservations/{$approved->id}/edit", $response);
    }

    public function test_remove_reservation_and_its_schedule(): void
    {
        $reservation = $this->createReservation(1);
        unset($_SESSION['flash']);

        $response = $this->post(
            action: 'destroy',
            controllerName: 'App\\Controllers\\ReservationsController',
            params: ['id' => (string) $reservation->id]
        );

        $this->assertEquals('Location: /reservations', $response);
        $this->assertEquals('Reserva removida com sucesso!', $_SESSION['flash']['success']);
        $this->assertNull(Reservation::findById($reservation->id));
        $this->assertCount(0, ReservationSchedule::where(['reservation_id' => $reservation->id]));
    }

    public function test_non_pending_reservation_cannot_be_removed(): void
    {
        $reservation = $this->createReservation(1);
        $reservation->status = Reservation::STATUS_APPROVED;
        $reservation->save();
        unset($_SESSION['flash']);

        $response = $this->post(
            action: 'destroy',
            controllerName: 'App\\Controllers\\ReservationsController',
            params: ['id' => (string) $reservation->id]
        );

        $this->assertEquals("Location: /reservations/{$reservation->id}", $response);
        $this->assertEquals('Somente reservas pendentes podem ser removidas.', $_SESSION['flash']['danger']);
        $this->assertNotNull(Reservation::findById($reservation->id));
        $this->assertCount(1, ReservationSchedule::where(['reservation_id' => $reservation->id]));
    }

    public function test_destroy_reservation_from_another_user_is_not_found(): void
    {
        $other = $this->newReservation(['user_id' => $this->createUser('outro@example.com')->id]);
        $other->save();

        try {
            $this->post(
                action: 'destroy',
                controllerName: 'App\\Controllers\\ReservationsController',
                params: ['id' => (string) $other->id]
            );
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Reserva não encontrada', $e->getMessage());
        }
    }

    public function test_destroy_nonexistent_reservation_is_not_found(): void
    {
        try {
            $this->post(
                action: 'destroy',
                controllerName: 'App\\Controllers\\ReservationsController',
                params: ['id' => '999']
            );
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Reserva não encontrada', $e->getMessage());
        }
    }

    public function test_remove_form_is_only_shown_for_pending_reservations(): void
    {
        $pending = $this->createReservation(1);
        $approved = $this->createReservation(2);
        $approved->status = Reservation::STATUS_APPROVED;
        $approved->save();

        $index = $this->get(action: 'index', controllerName: 'App\\Controllers\\ReservationsController');
        $this->assertStringContainsString("action=\"/reservations/{$pending->id}\"", $index);
        $this->assertStringContainsString("confirm('Deseja remover esta reserva?')", $index);
        $this->assertStringNotContainsString("action=\"/reservations/{$approved->id}\"", $index);

        $pendingShow = $this->get(
            action: 'show',
            controllerName: 'App\\Controllers\\ReservationsController',
            params: ['id' => (string) $pending->id]
        );
        $approvedShow = $this->get(
            action: 'show',
            controllerName: 'App\\Controllers\\ReservationsController',
            params: ['id' => (string) $approved->id]
        );

        $this->assertStringContainsString("action=\"/reservations/{$pending->id}\"", $pendingShow);
        $this->assertStringContainsString('<input type="hidden" name="_method" value="DELETE">', $pendingShow);
        $this->assertStringNotContainsString("action=\"/reservations/{$approved->id}\"", $approvedShow);
        $this->assertStringNotContainsString('<input type="hidden" name="_method" value="DELETE">', $approvedShow);
    }

    public function test_update_reservation_from_another_user_is_not_found(): void
    {
        $other = $this->newReservation(['user_id' => $this->createUser('outro@example.com')->id]);
        $other->save();

        $this->expectException(HTTPException::class);

        $this->put(
            action: 'update',
            controllerName: 'App\Controllers\ReservationsController',
            params: ['id' => (string) $other->id, 'reservation' => ['motive' => 'Invasão']]
        );
    }
}
