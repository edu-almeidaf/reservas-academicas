<?php

namespace Tests\Acceptance\Resources;

use App\Models\Resource;
use App\Models\ResourceImage;
use Core\Constants\Constants;
use Database\Populate\ResourceCategoriesPopulate;
use Database\Populate\ResourcesPopulate;
use RuntimeException;
use Tests\Support\AcceptanceTester;

trait ResourceGalleryFixtures
{
    private function populateResources(AcceptanceTester $page): void
    {
        $this->populateUsers();

        ob_start();
        ResourceCategoriesPopulate::populate();
        ResourcesPopulate::populate();
        ob_end_clean();

        $page->login('tecnico@utfpr.edu.br', '12345678');
    }

    // The database is recreated for each scenario, but the uploaded files are not
    private function clearGalleries(): void
    {
        foreach (Resource::all() as $resource) {
            $resource->gallery()->clear();
        }
    }

    private function findResource(string $name): Resource
    {
        $resource = Resource::findBy(['name' => $name]);
        if (!$resource instanceof Resource) {
            throw new RuntimeException("The resource fixture {$name} was not created.");
        }

        return $resource;
    }

    // The gallery upload only accepts files sent by HTTP, so the fixture is copied to the gallery folder
    private function createImage(Resource $resource, string $fixture = 'resource_image.jpg'): ResourceImage
    {
        $extension = pathinfo($fixture, PATHINFO_EXTENSION);

        /** @var ResourceImage $image */
        $image = $resource->images()->new([
            'file_path' => "resources/{$resource->id}/" . bin2hex(random_bytes(8)) . ".{$extension}",
            'mime_type' => $extension === 'png' ? 'image/png' : 'image/jpeg',
        ]);

        $directory = dirname($image->absolutePath());
        if (!is_dir($directory)) {
            mkdir(directory: $directory, recursive: true);
        }
        copy((string) Constants::rootPath()->join("tests/Support/Data/{$fixture}"), $image->absolutePath());

        // The folders are created by the test runner, but the web container (www-data) removes the files
        chmod(dirname($directory), 0777);
        chmod($directory, 0777);

        if (!$image->save()) {
            throw new RuntimeException('The image fixture could not be saved.');
        }

        return $image;
    }
}
