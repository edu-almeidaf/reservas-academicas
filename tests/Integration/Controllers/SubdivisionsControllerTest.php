<?php

namespace Tests\Integration\Controllers;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Subdivision;
use App\Models\User;
use Core\Exceptions\HTTPException;

class SubdivisionsControllerTest extends ControllerTestCase
{
    private const CONTROLLER = 'App\Controllers\SubdivisionsController';

    private Resource $resource;

    public function setUp(): void
    {
        parent::setUp();
        $user = new User([
            'profile' => User::PROFILE_STUDENT,
            'name' => 'Discente 1',
            'email' => 'discente@example.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        $user->save();
        $_SESSION['user']['id'] = $user->id;

        $category = new ResourceCategory(['name' => 'Laboratório']);
        $category->save();

        $this->resource = $this->createResource('Laboratório de Redes');
    }

    private function createResource(string $name, int $active = 1): Resource
    {
        $resource = new Resource([
            'resource_category_id' => ResourceCategory::findBy(['name' => 'Laboratório'])->id,
            'name' => $name,
            'active' => $active,
        ]);
        $resource->save();

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(string $resourceId): array
    {
        $response = $this->get(
            action: 'index',
            controllerName: self::CONTROLLER,
            params: ['resource_id' => $resourceId]
        );

        return json_decode($response, true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_index_should_return_the_active_subdivisions_ordered_by_name(): void
    {
        foreach ([['Rack de servidores', 1], ['Bancada B', 1], ['Bancada A', 1], ['Bancada C', 0]] as [$name, $active]) {
            $subdivision = $this->resource->subdivisions()->new(['name' => $name, 'active' => $active]);
            $subdivision->save();
        }

        $json = $this->getJson((string) $this->resource->id);

        $this->assertSame(['id' => $this->resource->id, 'name' => 'Laboratório de Redes'], $json['resource']);
        $this->assertSame(
            ['Bancada A', 'Bancada B', 'Rack de servidores'],
            array_column($json['subdivisions'], 'name')
        );
        $this->assertSame(
            Subdivision::findBy(['name' => 'Bancada A'])->id,
            $json['subdivisions'][0]['id']
        );
    }

    public function test_index_should_not_return_subdivisions_of_other_resources(): void
    {
        $other = $this->createResource('Laboratório 2');
        $subdivision = $other->subdivisions()->new(['name' => 'Bancada 1']);
        $subdivision->save();

        $json = $this->getJson((string) $this->resource->id);

        $this->assertSame([], $json['subdivisions']);
    }

    public function test_index_should_return_an_empty_list_for_resource_without_subdivisions(): void
    {
        $json = $this->getJson((string) $this->resource->id);

        $this->assertSame([], $json['subdivisions']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidResourceIdsProvider(): array
    {
        return [
            'nonexistent' => ['999'],
            'not numeric' => ['abc'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidResourceIdsProvider')]
    public function test_index_of_invalid_resource_is_not_found(string $id): void
    {
        $this->assertNotFound($id);
    }

    public function test_index_of_inactive_resource_is_not_found(): void
    {
        $inactive = $this->createResource('Laboratório de Eletrônica', 0);

        $this->assertNotFound((string) $inactive->id);
    }

    private function assertNotFound(string $resourceId): void
    {
        try {
            $this->get(action: 'index', controllerName: self::CONTROLLER, params: ['resource_id' => $resourceId]);
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Recurso não encontrado', $e->getMessage());
        }
    }
}
