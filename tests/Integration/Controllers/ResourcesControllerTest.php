<?php

namespace Tests\Integration\Controllers;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\User;
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
}
