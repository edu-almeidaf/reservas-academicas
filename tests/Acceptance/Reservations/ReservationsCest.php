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

class ReservationsCest extends BaseAcceptanceCest
{
    private int $studentId;

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
        if (!$student instanceof User) {
            throw new RuntimeException('The student fixture was not created.');
        }

        $this->studentId = $student->id;
        $page->login('discente@utfpr.edu.br', '12345678');
    }

    public function createReservationWithInvalidData(AcceptanceTester $page): void
    {
        $page->amOnPage('/reservations/new');
        $page->seeInCurrentUrl('/reservations/new');
        $page->see('Nova Reserva', '//h1');
        $this->fillReservationForm($page, $this->futureDate(3), '10:00', '09:00', 'Reserva inválida');
        $page->click('Solicitar');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Nova Reserva', '//h1');
        $page->see('Existem dados incorretos! Por favor, verifique!');
        $page->see('deve ser depois do horário de início!');
    }

    public function createReservationSuccessfully(AcceptanceTester $page): void
    {
        $page->amOnPage('/reservations/new');
        $page->seeInCurrentUrl('/reservations/new');
        $page->see('Nova Reserva', '//h1');
        $this->fillReservationForm($page, $this->futureDate(3), '09:00', '10:00', 'Aula de teste');
        $page->click('Solicitar');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->see('Reserva solicitada com sucesso!');
        $page->see('Laboratório de Informática 1');
    }

    public function updateReservationWithInvalidData(AcceptanceTester $page): void
    {
        $reservation = $this->createReservation($this->studentId, 3);
        $page->amOnPage("/reservations/{$reservation->id}/edit");
        $page->seeInCurrentUrl("/reservations/{$reservation->id}/edit");
        $page->see("Editar Reserva #{$reservation->id}", '//h1');
        $this->setScheduleFields($page, $this->futureDate(3), '09:00', '08:00');
        $page->click('Atualizar');

        $page->seeInCurrentUrl("/reservations/{$reservation->id}");
        $page->see("Editar Reserva #{$reservation->id}", '//h1');
        $page->see('Existem dados incorretos! Por favor, verifique!');
        $page->see('deve ser depois do horário de início!');
    }

    public function updateReservationSuccessfully(AcceptanceTester $page): void
    {
        $reservation = $this->createReservation($this->studentId, 3);
        $page->amOnPage("/reservations/{$reservation->id}/edit");
        $page->seeInCurrentUrl("/reservations/{$reservation->id}/edit");
        $page->see("Editar Reserva #{$reservation->id}", '//h1');
        $page->fillField('#reservation_motive', 'Motivo atualizado');
        $page->click('Atualizar');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->see('Reserva atualizada com sucesso!');
        $page->click("//a[normalize-space()='#{$reservation->id}']");
        $page->seeInCurrentUrl("/reservations/{$reservation->id}");
        $page->see("Reserva #{$reservation->id}", '//h1');
        $page->see('Motivo atualizado');
    }

    public function listAllReservations(AcceptanceTester $page): void
    {
        $this->createReservation($this->studentId, 3, '09:00', '10:00', 'Primeira reserva');
        $this->createReservation($this->studentId, 4, '09:00', '10:00', 'Segunda reserva');

        $page->amOnPage('/reservations');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->see($this->formattedDate($this->futureDate(3)));
        $page->see($this->formattedDate($this->futureDate(4)));
        $page->see('Pendente');
    }

    public function removeReservation(AcceptanceTester $page): void
    {
        $this->createReservation($this->studentId, 3);
        $page->amOnPage('/reservations');
        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->click('Remover');
        $page->acceptPopup();

        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->see('Reserva removida com sucesso!');
        $page->see('Você ainda não tem reservas.');
    }

    public function paginateReservations(AcceptanceTester $page): void
    {
        for ($dayOffset = 3; $dayOffset < 28; $dayOffset++) {
            $this->createReservation($this->studentId, $dayOffset);
        }

        $page->amOnPage('/reservations');
        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->see('Mostrando 1 - 10 de 25');
        $page->resizeWindow(1280, 1000);
        $page->click('3');
        $page->seeInCurrentUrl('/reservations/page/3');
        $page->see('Mostrando 21 - 25 de 25');
        $page->click('Anterior');
        $page->seeInCurrentUrl('/reservations/page/2');
        $page->see('Mostrando 11 - 20 de 25');
        $page->click('Próxima');
        $page->seeInCurrentUrl('/reservations/page/3');
        $page->see('Mostrando 21 - 25 de 25');
    }

    private function fillReservationForm(
        AcceptanceTester $page,
        string $date,
        string $startTime,
        string $endTime,
        string $motive
    ): void {
        $page->selectOption('#reservation_resource_id', 'Laboratório de Informática 1');
        $page->selectOption('#reservation_reservation_category_id', 'Aula');
        $this->setScheduleFields($page, $date, $startTime, $endTime);
        $page->fillField('#reservation_motive', $motive);
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
            'scheduled_date' => $this->futureDate($dayOffset),
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

    private function formattedDate(string $date): string
    {
        return (new \DateTimeImmutable($date))->format('d/m/Y');
    }
}
