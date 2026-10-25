<?php

namespace Tests\Unit\Models\Reservations;

use App\Models\Reservation;
use App\Models\ReservationCategory;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\User;

trait ReservationFixtures
{
    private User $user;
    private Resource $resource;
    private ReservationCategory $reservationCategory;

    private function createReservationFixtures(): void
    {
        $this->user = $this->createUser('discente@example.com');

        $resourceCategory = new ResourceCategory(['name' => 'Laboratório']);
        $resourceCategory->save();

        $this->resource = new Resource([
            'resource_category_id' => $resourceCategory->id,
            'name' => 'Laboratório 1',
            'active' => 1,
        ]);
        $this->resource->save();

        $this->reservationCategory = new ReservationCategory(['name' => 'Aula']);
        $this->reservationCategory->save();
    }

    private function createUser(string $email): User
    {
        $user = new User([
            'profile' => User::PROFILE_STUDENT,
            'name' => 'User',
            'email' => $email,
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);
        $user->save();

        return $user;
    }

    private function futureDate(int $days = 1): string
    {
        return date('Y-m-d', strtotime("+{$days} days"));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function newReservation(array $overrides = []): Reservation
    {
        return new Reservation(array_merge([
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'reservation_category_id' => $this->reservationCategory->id,
            'motive' => 'Aula de Banco de Dados',
            'scheduled_date' => $this->futureDate(),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ], $overrides));
    }
}
