<?php

namespace Tests\Integration\Controllers;

use App\Models\Reservation;
use App\Models\ReservationCategory;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceImage;
use App\Models\User;
use Core\Constants\Constants;
use Core\Exceptions\HTTPException;

class ResourcesControllerTest extends ControllerTestCase
{
    private Resource $resource;

    public function setUp(): void
    {
        parent::setUp();
        $user = new User([
            'profile' => User::PROFILE_ADMIN,
            'name' => 'Técnico 1',
            'email' => 'tecnico@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $user->save();
        $_SESSION['user']['id'] = $user->id;

        $category = new ResourceCategory(['name' => 'Laboratório']);
        $category->save();

        $this->resource = new Resource([
            'resource_category_id' => $category->id,
            'name' => 'Laboratório de Redes',
            'description' => 'Bancadas com switches e roteadores.',
            'active' => 1,
        ]);
        $this->resource->save();
    }

    public function tearDown(): void
    {
        $this->resource->gallery()->clear();
        parent::tearDown();
    }

    public function test_list_resources(): void
    {
        $inactive = new Resource([
            'resource_category_id' => $this->resource->resource_category_id,
            'name' => 'Laboratório de Eletrônica',
            'active' => 0,
        ]);
        $inactive->save();

        $response = $this->get(action: 'index', controllerName: 'App\Controllers\ResourcesController');

        $this->assertMatchesRegularExpression('/<h1>\s*Recursos\s*<\/h1>/', $response);
        $this->assertStringContainsString("href=\"/resources/{$this->resource->id}\"", $response);
        $this->assertStringContainsString('Laboratório de Redes', $response);
        $this->assertStringContainsString('Laboratório de Eletrônica', $response);
        $this->assertStringContainsString('<td>Laboratório</td>', $response);
        $this->assertStringContainsString('<td>Ativo</td>', $response);
        $this->assertStringContainsString('<td>Inativo</td>', $response);
    }

    public function test_show_resource_with_images(): void
    {
        $image = $this->resource->images()->new([
            'file_path' => "resources/{$this->resource->id}/9f2c4be1a07d43e6.jpg",
            'mime_type' => 'image/jpeg',
        ]);
        $image->save();

        $response = $this->get(
            action: 'show',
            controllerName: 'App\Controllers\ResourcesController',
            params: ['id' => (string) $this->resource->id]
        );

        $this->assertMatchesRegularExpression('/<h1>\s*Laboratório de Redes\s*<\/h1>/', $response);
        $this->assertStringContainsString('Bancadas com switches e roteadores.', $response);
        $this->assertStringContainsString(
            "<img src=\"/assets/uploads/resources/{$this->resource->id}/9f2c4be1a07d43e6.jpg\"",
            $response
        );
        $this->assertStringNotContainsString('Nenhuma imagem cadastrada.', $response);
    }

    public function test_show_resource_without_images(): void
    {
        $response = $this->get(
            action: 'show',
            controllerName: 'App\Controllers\ResourcesController',
            params: ['id' => (string) $this->resource->id]
        );

        $this->assertStringContainsString('Nenhuma imagem cadastrada.', $response);
        $this->assertStringNotContainsString('<img', $response);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidIdsProvider(): array
    {
        return [
            'nonexistent' => ['999'],
            'not numeric' => ['abc'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidIdsProvider')]
    public function test_show_invalid_resource_is_not_found(string $id): void
    {
        try {
            $this->get(action: 'show', controllerName: 'App\Controllers\ResourcesController', params: ['id' => $id]);
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Recurso não encontrado', $e->getMessage());
        }
    }

    public function test_destroy_resource_removes_images_and_files(): void
    {
        $images = [$this->createImage('resource_image.jpg'), $this->createImage('resource_image.png')];

        $response = $this->post(
            action: 'destroy',
            controllerName: 'App\Controllers\ResourcesController',
            params: ['id' => (string) $this->resource->id]
        );

        $this->assertEquals('Location: /resources', $response);
        $this->assertEquals('Recurso removido com sucesso!', $_SESSION['flash']['success']);
        $this->assertNull(Resource::findById($this->resource->id));
        $this->assertEmpty(ResourceImage::where(['resource_id' => $this->resource->id]));
        foreach ($images as $image) {
            $this->assertFileDoesNotExist($image->absolutePath());
        }
        $this->assertDirectoryDoesNotExist($this->resourceDirectory());
    }

    public function test_destroy_resource_without_images(): void
    {
        $response = $this->post(
            action: 'destroy',
            controllerName: 'App\Controllers\ResourcesController',
            params: ['id' => (string) $this->resource->id]
        );

        $this->assertEquals('Location: /resources', $response);
        $this->assertNull(Resource::findById($this->resource->id));
    }

    public function test_destroy_resource_with_reservations_is_blocked(): void
    {
        $image = $this->createImage('resource_image.jpg');
        $category = new ReservationCategory(['name' => 'Aula']);
        $category->save();
        $reservation = new Reservation([
            'user_id' => $_SESSION['user']['id'],
            'resource_id' => $this->resource->id,
            'reservation_category_id' => $category->id,
            'motive' => 'Aula de Redes',
            'scheduled_date' => date('Y-m-d', strtotime('+1 day')),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);
        $reservation->save();

        $response = $this->post(
            action: 'destroy',
            controllerName: 'App\Controllers\ResourcesController',
            params: ['id' => (string) $this->resource->id]
        );

        $this->assertEquals("Location: /resources/{$this->resource->id}", $response);
        $this->assertEquals(
            'Não é possível remover um recurso que já foi usado em reservas.',
            $_SESSION['flash']['danger']
        );
        $this->assertNotNull(Resource::findById($this->resource->id));
        $this->assertNotNull(ResourceImage::findById($image->id));
        $this->assertFileExists($image->absolutePath());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidIdsProvider')]
    public function test_destroy_invalid_resource_is_not_found(string $id): void
    {
        try {
            $this->post(action: 'destroy', controllerName: 'App\Controllers\ResourcesController', params: ['id' => $id]);
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Recurso não encontrado', $e->getMessage());
        }

        $this->assertNotNull(Resource::findById($this->resource->id));
    }

    private function createImage(string $fixture): ResourceImage
    {
        $extension = pathinfo($fixture, PATHINFO_EXTENSION);
        /** @var ResourceImage $image */
        $image = $this->resource->images()->new([
            'file_path' => "resources/{$this->resource->id}/" . bin2hex(random_bytes(8)) . ".{$extension}",
            'mime_type' => $extension === 'png' ? 'image/png' : 'image/jpeg',
        ]);
        $image->save();

        if (!is_dir($this->resourceDirectory())) {
            mkdir(directory: $this->resourceDirectory(), recursive: true);
        }
        copy((string) Constants::rootPath()->join("tests/files/{$fixture}"), $image->absolutePath());

        return $image;
    }

    private function resourceDirectory(): string
    {
        return (string) Constants::rootPath()->join("public/assets/uploads/resources/{$this->resource->id}");
    }
}
