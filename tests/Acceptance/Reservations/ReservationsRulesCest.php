<?php

namespace Tests\Acceptance\Reservations;

use App\Models\Reservation;
use App\Models\User;
use Database\Populate\ReservationCategoriesPopulate;
use Database\Populate\ResourceCategoriesPopulate;
use Database\Populate\ResourcesPopulate;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;
use RuntimeException;

class ReservationsRulesCest extends BaseAcceptanceCest
{
    private int $studentId;
    private int $otherUserId;

    public function _before(AcceptanceTester $page): void
    {
        parent::_before($page);
        $this->populateUsers();

        ob_start();
        ResourceCategoriesPopulate::populate();
        ResourcesPopulate::populate();
        ReservationCategoriesPopulate::populate();
        ob_end_clean();

        $student = User::findByEmail('discente@utfpr.edu.br');
        $otherUser = User::findByEmail('docente@utfpr.edu.br');
        if (!$student instanceof User || !$otherUser instanceof User) {
            throw new RuntimeException('The user fixtures were not created.');
        }

        $this->studentId = $student->id;
        $this->otherUserId = $otherUser->id;
        $page->login('discente@utfpr.edu.br', '12345678');
    }

    public function reservationScheduleConflictIsRejected(AcceptanceTester $page): void
    {
        $reservation = $this->createReservation($this->studentId, 3, '09:00', '10:00');
        $page->amOnPage('/reservations/new');
        $page->seeInCurrentUrl('/reservations/new');
        $page->see('Nova Reserva', '//h1');
        $page->selectOption('#reservation_resource_id', 'Laboratório de Informática 1');
        $page->selectOption('#reservation_reservation_category_id', 'Aula');
        $this->setScheduleFields($page, $this->futureDate(3), '09:30', '10:30');
        $page->fillField('#reservation_motive', 'Reserva em conflito');
        $page->click('Solicitar');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Nova Reserva', '//h1');
        $page->see('Existem dados incorretos! Por favor, verifique!');
        $page->see('Horário indisponível para reserva.');
    }

    public function approvedReservationCannotBeChangedOrRemoved(AcceptanceTester $page): void
    {
        $reservation = $this->createReservation($this->studentId, 3);
        $reservation->status = Reservation::STATUS_APPROVED;
        if (!$reservation->save()) {
            throw new RuntimeException('The approved reservation fixture could not be saved.');
        }

        $page->amOnPage("/reservations/{$reservation->id}");
        $page->seeInCurrentUrl("/reservations/{$reservation->id}");
        $page->see("Reserva #{$reservation->id}", '//h1');
        $page->see('Deferida');
        $page->dontSee('Editar');
        $page->dontSee('Remover');

        $page->amOnPage("/reservations/{$reservation->id}/edit");
        $page->seeInCurrentUrl("/reservations/{$reservation->id}");
        $page->see("Reserva #{$reservation->id}", '//h1');
        $page->see('Somente reservas pendentes podem ser alteradas.');

        $page->executeJS(
            "const form = document.createElement('form');" .
            "form.method = 'POST';" .
            "form.action = '/reservations/{$reservation->id}';" .
            "const method = document.createElement('input');" .
            "method.type = 'hidden';" .
            "method.name = '_method';" .
            "method.value = 'DELETE';" .
            "form.appendChild(method);" .
            "document.body.appendChild(form);" .
            'form.submit();'
        );
        $page->seeInCurrentUrl("/reservations/{$reservation->id}");
        $page->see("Reserva #{$reservation->id}", '//h1');
        $page->see('Somente reservas pendentes podem ser removidas.');
        $page->see('Deferida');
    }

    public function reservationsFromOtherUsersAreNotListed(AcceptanceTester $page): void
    {
        $this->createReservation($this->studentId, 3, motive: 'Minha reserva');
        $this->createReservation(
            $this->otherUserId,
            30,
            '11:00',
            '12:00',
            'Reserva de outro usuário'
        );

        $page->amOnPage('/reservations');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->see($this->formattedDate(3));
        $page->dontSee($this->formattedDate(30));
    }

    private function createReservation(
        int $userId,
        int $dayOffset,
        string $startTime = '09:00',
        string $endTime = '10:00',
        string $motive = 'Reserva de teste'
    ): Reservation {
        $reservation = new Reservation([
            'user_id' => $userId,
            'resource_id' => 1,
            'reservation_category_id' => 1,
            'motive' => $motive,
            'scheduled_date' => (new \DateTimeImmutable("+{$dayOffset} days"))->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        if (!$reservation->save()) {
            throw new RuntimeException('The reservation fixture could not be saved.');
        }

        return $reservation;
    }

    private function futureDate(int $dayOffset): string
    {
        return (new \DateTimeImmutable("+{$dayOffset} days"))->format('Y-m-d');
    }

    private function formattedDate(int $dayOffset): string
    {
        return (new \DateTimeImmutable("+{$dayOffset} days"))->format('d/m/Y');
    }

    private function setScheduleFields(
        AcceptanceTester $page,
        string $date,
        string $startTime,
        string $endTime
    ): void {
        $page->executeJS(
            "document.querySelector('#reservation_scheduled_date').value = '{$date}';" .
            "document.querySelector('#reservation_start_time').value = '{$startTime}';" .
            "document.querySelector('#reservation_end_time').value = '{$endTime}';"
        );
    }
}
