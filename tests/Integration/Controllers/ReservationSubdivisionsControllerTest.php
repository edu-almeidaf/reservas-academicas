<?php

namespace Tests\Integration\Controllers;

use App\Models\Reservation;
use App\Models\ReservationSubdivision;
use App\Models\Subdivision;
use Core\Exceptions\HTTPException;
use Tests\Unit\Models\Reservations\ReservationFixtures;

class ReservationSubdivisionsControllerTest extends ControllerTestCase
{
    use ReservationFixtures;

    private const CONTROLLER = 'App\Controllers\ReservationSubdivisionsController';

    private Reservation $reservation;
    /** @var int[] */
    private array $subdivisionIds = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->createReservationFixtures();
        $_SESSION['user']['id'] = $this->user->id;

        foreach (['Bancada 1', 'Bancada 2', 'Bancada 3'] as $name) {
            $subdivision = $this->resource->subdivisions()->new(['name' => $name]);
            $subdivision->save();
            $this->subdivisionIds[] = $subdivision->id;
        }

        $this->reservation = $this->newReservation(['subdivision_ids' => array_slice($this->subdivisionIds, 0, 2)]);
        $this->reservation->save();
    }

    private function destroy(string $reservationId, string $subdivisionId): string
    {
        return $this->post(
            action: 'destroy',
            controllerName: self::CONTROLLER,
            params: ['reservation_id' => $reservationId, 'id' => $subdivisionId]
        );
    }

    public function test_destroy_should_remove_only_the_link(): void
    {
        $response = $this->destroy((string) $this->reservation->id, (string) $this->subdivisionIds[0]);

        $this->assertEquals("Location: /reservations/{$this->reservation->id}", $response);
        $this->assertEquals('Subdivisão removida da reserva!', $_SESSION['flash']['success']);
        $this->assertSame([$this->subdivisionIds[1]], $this->reservation->subdivisionIds());
        $this->assertNotNull(Subdivision::findById($this->subdivisionIds[0]));
    }

    public function test_destroy_on_approved_reservation_is_denied(): void
    {
        $this->reservation->status = Reservation::STATUS_APPROVED;
        $this->reservation->save();

        $response = $this->destroy((string) $this->reservation->id, (string) $this->subdivisionIds[0]);

        $this->assertEquals("Location: /reservations/{$this->reservation->id}", $response);
        $this->assertEquals('Somente reservas pendentes podem ser alteradas.', $_SESSION['flash']['danger']);
        $this->assertCount(2, ReservationSubdivision::all());
    }

    public function test_destroy_on_reservation_of_another_user_is_not_found(): void
    {
        $other = $this->newReservation([
            'user_id' => $this->createUser('outro@example.com')->id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'subdivision_ids' => [$this->subdivisionIds[0]],
        ]);
        $other->save();

        $this->assertNotFound((string) $other->id, (string) $this->subdivisionIds[0], 'Reserva não encontrada');
        $this->assertSame([$this->subdivisionIds[0]], $other->subdivisionIds());
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function invalidIdsProvider(): array
    {
        return [
            'nonexistent reservation' => ['999', 'first', 'Reserva não encontrada'],
            'reservation not numeric' => ['abc', 'first', 'Reserva não encontrada'],
            'subdivision not in the reservation' => ['reservation', 'third', 'Subdivisão não encontrada na reserva'],
            'subdivision not numeric' => ['reservation', 'abc', 'Subdivisão não encontrada na reserva'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidIdsProvider')]
    public function test_destroy_with_invalid_ids_is_not_found(string $reservation, string $subdivision, string $message): void
    {
        $ids = ['reservation' => (string) $this->reservation->id];
        $subdivisions = ['first' => (string) $this->subdivisionIds[0], 'third' => (string) $this->subdivisionIds[2]];

        $this->assertNotFound($ids[$reservation] ?? $reservation, $subdivisions[$subdivision] ?? $subdivision, $message);
        $this->assertCount(2, ReservationSubdivision::all());
    }

    private function assertNotFound(string $reservationId, string $subdivisionId, string $message): void
    {
        try {
            $this->destroy($reservationId, $subdivisionId);
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals($message, $e->getMessage());
        }
    }
}
