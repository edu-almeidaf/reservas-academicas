<?php

namespace Tests\Acceptance\Reservations;

use App\Models\Reservation;
use App\Models\Subdivision;
use App\Models\User;
use Database\Populate\ReservationCategoriesPopulate;
use Database\Populate\ResourceCategoriesPopulate;
use Database\Populate\ResourcesPopulate;
use Database\Populate\SubdivisionsPopulate;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class ReservationSubdivisionsCest extends BaseAcceptanceCest
{
    private const SUBDIVISIONS = '#reservation_subdivisions';

    private int $studentId;

    public function _before(AcceptanceTester $page): void
    {
        parent::_before($page);
        $this->populateUsers();

        ob_start();
        ResourceCategoriesPopulate::populate();
        ResourcesPopulate::populate();
        SubdivisionsPopulate::populate();
        ReservationCategoriesPopulate::populate();
        ob_end_clean();

        $student = User::findByEmail('discente@utfpr.edu.br');
        if (!$student instanceof User) {
            throw new RuntimeException('The student fixture was not created.');
        }

        $this->studentId = $student->id;
        $page->login('discente@utfpr.edu.br', '12345678');
        // The subdivisions block makes the form taller than the default headless window
        $page->resizeWindow(1280, 1200);
    }

    public function loadSubdivisionsWithAjax(AcceptanceTester $page): void
    {
        $page->amOnPage('/reservations/new');
        $page->seeInCurrentUrl('/reservations/new');
        $page->see('Nova Reserva', '//h1');

        $page->selectOption('#reservation_resource_id', 'Laboratório de Informática 1');
        $page->waitForElement(self::SUBDIVISIONS . ' input[type=checkbox]', 5);
        $page->see('Bancada 1', self::SUBDIVISIONS);
        $page->dontSee('Bancada 4', self::SUBDIVISIONS);

        $page->selectOption('#reservation_resource_id', 'Laboratório de Redes');
        $page->waitForText('Bancada A', 5, self::SUBDIVISIONS);
        $page->dontSee('Bancada 1', self::SUBDIVISIONS);

        $page->selectOption('#reservation_resource_id', 'Laboratório de Informática 2');
        $page->waitForText('Este recurso não tem subdivisões', 5);
        $page->dontSeeElement(self::SUBDIVISIONS . ' input[type=checkbox]');
    }

    public function registerReservationWithSubdivisions(AcceptanceTester $page): void
    {
        $page->amOnPage('/reservations/new');
        $page->seeInCurrentUrl('/reservations/new');
        $page->see('Nova Reserva', '//h1');
        $page->selectOption('#reservation_resource_id', 'Laboratório de Informática 1');
        $page->waitForElement(self::SUBDIVISIONS . ' input[type=checkbox]', 5);
        $page->checkOption('Bancada 1');
        $page->checkOption('Bancada 2');
        $page->selectOption('#reservation_reservation_category_id', 'Aula');
        $this->setScheduleFields($page, $this->futureDate(3), '09:00', '10:00');
        $page->fillField('#reservation_motive', 'Aula com bancadas');
        $page->click('Solicitar');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->see('Reserva solicitada com sucesso!');

        $reservation = Reservation::findBy(['motive' => 'Aula com bancadas']);
        Assert::assertInstanceOf(Reservation::class, $reservation);
        Assert::assertCount(2, $reservation->subdivisionIds());

        $page->amOnPage("/reservations/{$reservation->id}");
        $page->seeInCurrentUrl("/reservations/{$reservation->id}");
        $page->see("Reserva #{$reservation->id}", '//h1');
        $page->see('Bancada 1');
        $page->see('Bancada 2');
    }

    public function viewReservationSubdivisions(AcceptanceTester $page): void
    {
        $reservation = $this->createReservation(['Bancada 1', 'Bancada 2']);

        $page->amOnPage("/reservations/{$reservation->id}");

        $page->seeInCurrentUrl("/reservations/{$reservation->id}");
        $page->see("Reserva #{$reservation->id}", '//h1');
        $page->see('Subdivisões');
        $page->see('Bancada 1', '.list-unstyled');
        $page->see('Bancada 2', '.list-unstyled');
    }

    public function removeSubdivisionFromReservation(AcceptanceTester $page): void
    {
        $reservation = $this->createReservation(['Bancada 1', 'Bancada 2']);
        $page->amOnPage("/reservations/{$reservation->id}");
        $page->see("Reserva #{$reservation->id}", '//h1');
        $page->click("//li[contains(., 'Bancada 1')]//button[normalize-space()='Remover']");
        $page->acceptPopup();

        $page->seeInCurrentUrl("/reservations/{$reservation->id}");
        $page->see("Reserva #{$reservation->id}", '//h1');
        $page->see('Subdivisão removida da reserva!');
        $page->dontSee('Bancada 1', '.list-unstyled');
        $page->see('Bancada 2', '.list-unstyled');
    }

    public function uncheckSubdivisionOnEdit(AcceptanceTester $page): void
    {
        $reservation = $this->createReservation(['Bancada 1', 'Bancada 2']);
        $page->amOnPage("/reservations/{$reservation->id}/edit");
        $page->seeInCurrentUrl("/reservations/{$reservation->id}/edit");
        $page->see("Editar Reserva #{$reservation->id}", '//h1');
        $page->waitForElement(self::SUBDIVISIONS . ' input[type=checkbox]', 5);
        $page->seeCheckboxIsChecked('Bancada 1');
        $page->seeCheckboxIsChecked('Bancada 2');
        $page->uncheckOption('Bancada 1');
        $page->click('Atualizar');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->see('Reserva atualizada com sucesso!');

        $page->amOnPage("/reservations/{$reservation->id}");
        $page->see("Reserva #{$reservation->id}", '//h1');
        $page->dontSee('Bancada 1', '.list-unstyled');
        $page->see('Bancada 2', '.list-unstyled');
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

    /**
     * @param string[] $subdivisionNames subdivisions of "Laboratório de Informática 1"
     */
    private function createReservation(array $subdivisionNames): Reservation
    {
        $subdivisionIds = array_map(function (string $name): int {
            $subdivision = Subdivision::findBy(['name' => $name, 'resource_id' => 1]);
            if (!$subdivision instanceof Subdivision) {
                throw new RuntimeException("The subdivision fixture {$name} was not created.");
            }

            return $subdivision->id;
        }, $subdivisionNames);

        $reservation = new Reservation([
            'user_id' => $this->studentId,
            'resource_id' => 1,
            'reservation_category_id' => 1,
            'motive' => 'Reserva de teste',
            'scheduled_date' => $this->futureDate(3),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'subdivision_ids' => $subdivisionIds,
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
}
