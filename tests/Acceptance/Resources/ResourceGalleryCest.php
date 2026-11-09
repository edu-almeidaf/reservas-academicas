<?php

namespace Tests\Acceptance\Resources;

use App\Models\Resource;
use PHPUnit\Framework\Assert;
use Tests\Acceptance\BaseAcceptanceCest;
use Tests\Support\AcceptanceTester;

class ResourceGalleryCest extends BaseAcceptanceCest
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

    public function uploadImage(AcceptanceTester $page): void
    {
        $page->amOnPage('/resources');
        $page->see('Recursos', '//h1');
        $page->click('Laboratório de Redes');
        $page->seeInCurrentUrl("/resources/{$this->resource->id}");
        $page->see('Laboratório de Redes', '//h1');
        $page->see('Nenhuma imagem cadastrada.');

        $page->attachFile('#resource_image', 'resource_image.jpg');
        $page->click('Enviar imagem');

        $page->seeInCurrentUrl("/resources/{$this->resource->id}");
        $page->see('Laboratório de Redes', '//h1');
        $page->see('Imagem enviada com sucesso!');
        $page->seeNumberOfElements('img.img-thumbnail', 1);
        $page->seeElement("img.img-thumbnail[src^='/assets/uploads/resources/{$this->resource->id}/']");
        $page->dontSee('Nenhuma imagem cadastrada.');
    }

    public function viewImages(AcceptanceTester $page): void
    {
        $jpeg = $this->createImage($this->resource, 'resource_image.jpg');
        $png = $this->createImage($this->resource, 'resource_image.png');

        $page->amOnPage("/resources/{$this->resource->id}");

        $page->seeInCurrentUrl("/resources/{$this->resource->id}");
        $page->see('Laboratório de Redes', '//h1');
        $page->see('Imagens', '//h2');
        $page->seeNumberOfElements('img.img-thumbnail', 2);
        $page->seeElement('img.img-thumbnail', ['src' => $jpeg->url()]);
        $page->seeElement('img.img-thumbnail', ['src' => $png->url()]);
    }

    public function removeImage(AcceptanceTester $page): void
    {
        $image = $this->createImage($this->resource);

        $page->amOnPage("/resources/{$this->resource->id}");
        $page->see('Laboratório de Redes', '//h1');
        $page->seeNumberOfElements('img.img-thumbnail', 1);

        $page->click("//button[normalize-space()='Remover']");
        $page->acceptPopup();

        $page->seeInCurrentUrl("/resources/{$this->resource->id}");
        $page->see('Laboratório de Redes', '//h1');
        $page->see('Imagem removida com sucesso!');
        $page->dontSeeElement('img.img-thumbnail');
        $page->see('Nenhuma imagem cadastrada.');
        Assert::assertFileDoesNotExist($image->absolutePath());
    }
}
