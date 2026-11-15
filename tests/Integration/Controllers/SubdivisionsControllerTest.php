<?php

namespace Tests\Integration\Controllers;

use App\Models\Reservation;
use App\Models\ReservationCategory;
use App\Models\ReservationSubdivision;
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

    /**
     * @param array<string, mixed> $subdivision
     */
    private function createSubdivision(?string $resourceId = null, array $subdivision = ['name' => 'Bancada 1']): string
    {
        return $this->post(
            action: 'create',
            controllerName: self::CONTROLLER,
            params: ['resource_id' => $resourceId ?? (string) $this->resource->id, 'subdivision' => $subdivision]
        );
    }

    private function destroySubdivision(string $resourceId, string $id): string
    {
        return $this->post(
            action: 'destroy',
            controllerName: self::CONTROLLER,
            params: ['resource_id' => $resourceId, 'id' => $id]
        );
    }

    private function assertCreateRejected(string $response, string $error, int $expectedCount = 0): void
    {
        $this->assertStringNotContainsString('Location:', $response);
        $this->assertMatchesRegularExpression('/<h1>\s*Laboratório de Redes\s*<\/h1>/', $response);
        $this->assertStringContainsString('is-invalid', $response);
        $this->assertStringContainsString($error, $response);
        $this->assertStringContainsString(
            'Não foi possível cadastrar a subdivisão! Por favor, verifique!',
            $response
        );
        $this->assertCount($expectedCount, $this->resource->subdivisions()->get());
    }

    public function test_create_subdivision(): void
    {
        $response = $this->createSubdivision();

        $this->assertEquals("Location: /resources/{$this->resource->id}", $response);
        $this->assertEquals('Subdivisão cadastrada com sucesso!', $_SESSION['flash']['success']);

        $subdivision = Subdivision::findBy(['name' => 'Bancada 1']);
        $this->assertNotNull($subdivision);
        $this->assertEquals($this->resource->id, $subdivision->resource_id);
        $this->assertTrue($subdivision->isActive());
    }

    public function test_create_subdivision_with_empty_name_is_rejected(): void
    {
        $response = $this->createSubdivision(subdivision: ['name' => '']);

        $this->assertCreateRejected($response, 'não pode ser vazio!');
    }

    public function test_create_subdivision_with_duplicated_name_is_rejected(): void
    {
        $this->createSubdivision();

        $response = $this->createSubdivision();

        $this->assertCreateRejected($response, 'já existe um registro com esse dado', 1);
    }

    public function test_create_subdivision_with_the_name_of_another_resource_is_allowed(): void
    {
        $other = $this->createResource('Laboratório 2');
        $this->createSubdivision((string) $other->id);

        $response = $this->createSubdivision();

        $this->assertEquals("Location: /resources/{$this->resource->id}", $response);
        $this->assertCount(1, $this->resource->subdivisions()->get());
        $this->assertCount(1, $other->subdivisions()->get());
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function malformedPayloadsProvider(): array
    {
        return [
            'without subdivision key' => [[]],
            'subdivision as string' => [['subdivision' => 'Bancada 1']],
            'name as array' => [['subdivision' => ['name' => ['Bancada 1']]]],
            'without name' => [['subdivision' => ['active' => '0']]],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('malformedPayloadsProvider')]
    public function test_create_subdivision_with_malformed_payload_is_rejected(array $payload): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: self::CONTROLLER,
            params: ['resource_id' => (string) $this->resource->id] + $payload
        );

        $this->assertCreateRejected($response, 'não pode ser vazio!');
    }

    public function test_create_subdivision_ignores_unknown_fields(): void
    {
        $this->createSubdivision(subdivision: ['name' => 'Bancada 1', 'active' => '0', 'resource_id' => '999']);

        $subdivision = Subdivision::findBy(['name' => 'Bancada 1']);
        $this->assertEquals($this->resource->id, $subdivision->resource_id);
        $this->assertTrue($subdivision->isActive());
    }

    public function test_create_subdivision_in_inactive_resource_is_allowed(): void
    {
        $inactive = $this->createResource('Laboratório de Eletrônica', 0);

        $response = $this->createSubdivision((string) $inactive->id);

        $this->assertEquals("Location: /resources/{$inactive->id}", $response);
        $this->assertCount(1, $inactive->subdivisions()->get());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidResourceIdsProvider')]
    public function test_create_subdivision_in_invalid_resource_is_not_found(string $id): void
    {
        try {
            $this->createSubdivision($id);
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Recurso não encontrado', $e->getMessage());
        }

        $this->assertNull(Subdivision::findBy(['name' => 'Bancada 1']));
    }

    public function test_destroy_subdivision(): void
    {
        $subdivision = $this->resource->subdivisions()->new(['name' => 'Bancada 1']);
        $subdivision->save();

        $response = $this->destroySubdivision((string) $this->resource->id, (string) $subdivision->id);

        $this->assertEquals("Location: /resources/{$this->resource->id}", $response);
        $this->assertEquals('Subdivisão removida com sucesso!', $_SESSION['flash']['success']);
        $this->assertNull(Subdivision::findById($subdivision->id));
    }

    public function test_destroy_subdivision_used_in_reservation_is_blocked(): void
    {
        $subdivision = $this->resource->subdivisions()->new(['name' => 'Bancada 1']);
        $subdivision->save();
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
        $link = new ReservationSubdivision(['reservation_id' => $reservation->id, 'subdivision_id' => $subdivision->id]);
        $link->save();

        $response = $this->destroySubdivision((string) $this->resource->id, (string) $subdivision->id);

        $this->assertEquals("Location: /resources/{$this->resource->id}", $response);
        $this->assertEquals(
            'Não é possível remover uma subdivisão que já foi usada em reservas.',
            $_SESSION['flash']['danger']
        );
        $this->assertNotNull(Subdivision::findById($subdivision->id));
        $this->assertTrue(ReservationSubdivision::exists(['subdivision_id' => $subdivision->id]));
    }

    public function test_destroy_subdivision_of_another_resource_is_not_found(): void
    {
        $other = $this->createResource('Laboratório 2');
        $subdivision = $other->subdivisions()->new(['name' => 'Bancada 1']);
        $subdivision->save();

        $this->assertDestroyNotFound((string) $this->resource->id, (string) $subdivision->id, 'Subdivisão não encontrada');
        $this->assertNotNull(Subdivision::findById($subdivision->id));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidSubdivisionIdsProvider(): array
    {
        return [
            'nonexistent' => ['999'],
            'not numeric' => ['abc'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidSubdivisionIdsProvider')]
    public function test_destroy_invalid_subdivision_is_not_found(string $id): void
    {
        $this->assertDestroyNotFound((string) $this->resource->id, $id, 'Subdivisão não encontrada');
    }

    public function test_destroy_subdivision_of_invalid_resource_is_not_found(): void
    {
        $subdivision = $this->resource->subdivisions()->new(['name' => 'Bancada 1']);
        $subdivision->save();

        $this->assertDestroyNotFound('999', (string) $subdivision->id, 'Recurso não encontrado');
        $this->assertNotNull(Subdivision::findById($subdivision->id));
    }

    private function assertDestroyNotFound(string $resourceId, string $id, string $message): void
    {
        try {
            $this->destroySubdivision($resourceId, $id);
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals($message, $e->getMessage());
        }
    }
}
