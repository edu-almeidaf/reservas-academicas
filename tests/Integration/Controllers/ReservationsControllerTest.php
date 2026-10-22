<?php

namespace Tests\Integration\Controllers;

use App\Models\Reservation;
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
        $this->assertStringContainsString("<td>{$reservation->id}</td>", $response);
        $this->assertStringNotContainsString("<td>{$other->id}</td>", $response);
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
}
