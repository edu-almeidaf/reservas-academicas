<?php

namespace Database\Populate;

use App\Models\ReservationCategory;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\User;

class ReservationsPopulate
{
    /** @var array<string, array{0: int, 1: string, 2: string}> */
    private const RESERVATIONS_BY_USER = [
        'discente@utfpr.edu.br' => [25, '08:00', '10:00'],
        'docente@utfpr.edu.br' => [5, '14:00', '16:00'],
        'tecnico@utfpr.edu.br' => [3, '19:00', '21:00'],
    ];

    private const APPROVED_STUDENT_RESERVATIONS = 2;

    public static function populate(): void
    {
        $resourceIds = array_keys(Resource::activeOptions());
        $categoryIds = array_keys(ReservationCategory::options());
        $numberOfReservations = 0;

        foreach (self::RESERVATIONS_BY_USER as $email => [$total, $start, $end]) {
            $user = User::findByEmail($email);

            for ($i = 1; $i <= $total; $i++) {
                $reservation = $user->reservations()->new([
                    'resource_id' => $resourceIds[$i % count($resourceIds)],
                    'reservation_category_id' => $categoryIds[$i % count($categoryIds)],
                    'motive' => "Reserva de exemplo {$i}",
                    'scheduled_date' => date('Y-m-d', strtotime("+{$i} days")),
                    'start_time' => $start,
                    'end_time' => $end,
                ]);

                if ($user->isStudent() && $i <= self::APPROVED_STUDENT_RESERVATIONS) {
                    $reservation->status = Reservation::STATUS_APPROVED;
                }

                $reservation->save();
                $numberOfReservations++;
            }
        }

        echo "Reservations populated with $numberOfReservations registers\n";
    }
}
