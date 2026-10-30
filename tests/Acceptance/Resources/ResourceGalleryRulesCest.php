<?php

namespace Tests\Acceptance\Resources;

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceImage;
use App\Models\User;
use Database\Populate\ReservationCategoriesPopulate;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class ResourceGalleryRulesCest extends BaseAcceptanceCest
{
    use ResourceGalleryFixtures;

    private Resource $resource;

    public function _before(AcceptanceTester $page): void
    {
        parent::_before($page);
        $this->populateResources($page);
        $this->resource = $this->findResource('Laboratório de Redes');
    }

    public function _after(AcceptanceTester $page): void
    {
        $this->clearGalleries();
        parent::_after($page);
    }

    public function rejectScriptDisguisedAsImage(AcceptanceTester $page): void
    {
        $this->uploadFile($page, 'malicious.jpg');

        $this->seeUploadRejected($page, 'O conteúdo do arquivo não é uma imagem JPEG ou PNG.');
    }

    public function rejectInvalidExtension(AcceptanceTester $page): void
    {
        $this->uploadFile($page, 'malicious.php');

        $this->seeUploadRejected($page, 'Formato inválido. Envie uma imagem JPEG ou PNG.');
    }

    public function rejectUploadWithoutFile(AcceptanceTester $page): void
    {
        $page->amOnPage("/resources/{$this->resource->id}");
        $page->see('Laboratório de Redes', '//h1');
        $page->click('Enviar imagem');

        $this->seeUploadRejected($page, 'Selecione uma imagem.');
    }

    public function removeResourceWithoutReservations(AcceptanceTester $page): void
    {
        $resource = $this->findResource('Laboratório de Eletrônica');
        $image = $this->createImage($resource);

        $page->amOnPage("/resources/{$resource->id}");
        $page->see('Laboratório de Eletrônica', '//h1');
        $page->seeNumberOfElements('img.img-thumbnail', 1);

        $page->resizeWindow(1280, 1000);
        $page->click('Remover recurso');
        $page->acceptPopup();

        $page->seeCurrentUrlEquals('/resources');
        $page->see('Recursos', '//h1');
        $page->see('Recurso removido com sucesso!');
        $page->dontSee('Laboratório de Eletrônica');
        Assert::assertNull(ResourceImage::findById($image->id));
        Assert::assertFileDoesNotExist($image->absolutePath());
    }

    public function blockRemovalOfReservedResource(AcceptanceTester $page): void
    {
        $image = $this->createImage($this->resource);
        $this->createReservation($this->resource);

        $page->amOnPage("/resources/{$this->resource->id}");
        $page->see('Laboratório de Redes', '//h1');

        $page->resizeWindow(1280, 1000);
        $page->click('Remover recurso');
        $page->acceptPopup();

        $page->seeCurrentUrlEquals("/resources/{$this->resource->id}");
        $page->see('Laboratório de Redes', '//h1');
        $page->see('Não é possível remover um recurso que já foi usado em reservas.');
        $page->seeNumberOfElements('img.img-thumbnail', 1);
        Assert::assertNotNull(Resource::findById($this->resource->id));
        Assert::assertFileExists($image->absolutePath());
    }

    private function uploadFile(AcceptanceTester $page, string $fixture): void
    {
        $page->amOnPage("/resources/{$this->resource->id}");
        $page->see('Laboratório de Redes', '//h1');
        $page->attachFile('#resource_image', $fixture);
        $page->click('Enviar imagem');
    }

    private function seeUploadRejected(AcceptanceTester $page, string $error): void
    {
        $page->seeInCurrentUrl("/resources/{$this->resource->id}/images");
        $page->see('Laboratório de Redes', '//h1');
        $page->see('Não foi possível enviar a imagem! Por favor, verifique!');
        $page->see($error, '.invalid-feedback');
        $page->dontSeeElement('img.img-thumbnail');
        Assert::assertEmpty($this->resource->images()->get());
    }

    private function createReservation(Resource $resource): void
    {
        ob_start();
        ReservationCategoriesPopulate::populate();
        ob_end_clean();

        $student = User::findByEmail('discente@utfpr.edu.br');
        if (!$student instanceof User) {
            throw new RuntimeException('The student fixture was not created.');
        }

        $reservation = $student->reservations()->new([
            'resource_id' => $resource->id,
            'reservation_category_id' => 1,
            'motive' => 'Aula de Redes',
            'scheduled_date' => (new \DateTimeImmutable('+3 days'))->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        if (!$reservation->save()) {
            throw new RuntimeException('The reservation fixture could not be saved.');
        }
    }
}
