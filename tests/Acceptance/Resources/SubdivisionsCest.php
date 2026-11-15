<?php

namespace Tests\Acceptance\Resources;

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Subdivision;
use App\Models\User;
use Database\Populate\ReservationCategoriesPopulate;
use Database\Populate\ResourceCategoriesPopulate;
use Database\Populate\ResourcesPopulate;
use Database\Populate\SubdivisionsPopulate;
use RuntimeException;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class SubdivisionsCest extends BaseAcceptanceCest
{
    private const RESOURCE = 'Laboratório de Informática 1';

    private Resource $resource;

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

        $resource = Resource::findBy(['name' => self::RESOURCE]);
        if (!$resource instanceof Resource) {
            throw new RuntimeException('The resource fixture was not created.');
        }

        $this->resource = $resource;
        // The subdivisions form and the reservation form are taller than the default headless window
        $page->resizeWindow(1280, 1200);
    }

    public function registerSubdivision(AcceptanceTester $page): void
    {
        $page->login('tecnico@utfpr.edu.br', '12345678');
        $page->amOnPage("/resources/{$this->resource->id}");
        $page->seeInCurrentUrl("/resources/{$this->resource->id}");
        $page->see(self::RESOURCE, '//h1');
        $page->fillField('#subdivision_name', 'Bancada 5');
        $page->click('Cadastrar subdivisão');

        $page->seeInCurrentUrl("/resources/{$this->resource->id}");
        $page->see(self::RESOURCE, '//h1');
        $page->see('Subdivisão cadastrada com sucesso!');
        $page->see('Bancada 5', 'table');
    }

    public function removeUnusedSubdivision(AcceptanceTester $page): void
    {
        $page->login('tecnico@utfpr.edu.br', '12345678');
        $page->amOnPage("/resources/{$this->resource->id}");
        $page->see(self::RESOURCE, '//h1');
        $page->click("//tr[contains(., 'Bancada 3')]//button[normalize-space()='Remover']");
        $page->acceptPopup();

        $page->seeInCurrentUrl("/resources/{$this->resource->id}");
        $page->see(self::RESOURCE, '//h1');
        $page->see('Subdivisão removida com sucesso!');
        $page->dontSee('Bancada 3', 'table');
    }

    public function blockRemovalOfSubdivisionWithReservations(AcceptanceTester $page): void
    {
        $this->createReservation('docente@utfpr.edu.br', 'Bancada 1', '09:00', '10:00');
        $page->login('tecnico@utfpr.edu.br', '12345678');
        $page->amOnPage("/resources/{$this->resource->id}");
        $page->see(self::RESOURCE, '//h1');
        $page->click("//tr[contains(., 'Bancada 1')]//button[normalize-space()='Remover']");
        $page->acceptPopup();

        $page->seeInCurrentUrl("/resources/{$this->resource->id}");
        $page->see(self::RESOURCE, '//h1');
        $page->see('Não é possível remover uma subdivisão que já foi usada em reservas.');
        $page->see('Bancada 1', 'table');
    }

    public function allowSameTimeOnDifferentSubdivisions(AcceptanceTester $page): void
    {
        $this->createReservation('docente@utfpr.edu.br', 'Bancada 1', '09:00', '10:00');
        $page->login('discente@utfpr.edu.br', '12345678');

        $this->requestReservation($page, 'Bancada 2');
        $page->click('Solicitar');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Minhas Reservas', '//h1');
        $page->see('Reserva solicitada com sucesso!');
    }

    public function rejectSameTimeOnSameSubdivision(AcceptanceTester $page): void
    {
        $this->createReservation('docente@utfpr.edu.br', 'Bancada 1', '09:00', '10:00');
        $page->login('discente@utfpr.edu.br', '12345678');

        $this->requestReservation($page, 'Bancada 1');
        $page->click('Solicitar');

        $page->seeInCurrentUrl('/reservations');
        $page->see('Nova Reserva', '//h1');
        $page->see('Existem dados incorretos! Por favor, verifique!');
        $page->see('Horário indisponível para reserva.');
    }

    private function requestReservation(AcceptanceTester $page, string $subdivisionName): void
    {
        $date = $this->futureDate();
        $page->amOnPage('/reservations/new');
        $page->see('Nova Reserva', '//h1');
        $page->selectOption('#reservation_resource_id', self::RESOURCE);
        $page->waitForElement('#reservation_subdivisions input[type=checkbox]', 5);
        $page->checkOption($subdivisionName);
        $page->selectOption('#reservation_reservation_category_id', 'Aula');
        $page->executeJS(
            "document.querySelector('#reservation_scheduled_date').value = '{$date}';" .
            "document.querySelector('#reservation_start_time').value = '09:00';" .
            "document.querySelector('#reservation_end_time').value = '10:00';"
        );
        $page->fillField('#reservation_motive', 'Aula na bancada');
    }

    private function createReservation(
        string $userEmail,
        string $subdivisionName,
        string $startTime,
        string $endTime
    ): Reservation {
        $user = User::findByEmail($userEmail);
        $subdivision = Subdivision::findBy(['name' => $subdivisionName, 'resource_id' => $this->resource->id]);
        if (!$user instanceof User || !$subdivision instanceof Subdivision) {
            throw new RuntimeException('The reservation fixture dependencies were not created.');
        }

        $reservation = new Reservation([
            'user_id' => $user->id,
            'resource_id' => $this->resource->id,
            'reservation_category_id' => 1,
            'motive' => 'Reserva de teste',
            'scheduled_date' => $this->futureDate(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'subdivision_ids' => [$subdivision->id],
        ]);

        if (!$reservation->save()) {
            throw new RuntimeException('The reservation fixture could not be saved.');
        }

        return $reservation;
    }

    private function futureDate(): string
    {
        return (new \DateTimeImmutable('+3 days'))->format('Y-m-d');
    }
}
