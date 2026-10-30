<?php

namespace Tests\Integration\Controllers;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceImage;
use App\Models\User;
use Core\Constants\Constants;
use Core\Exceptions\HTTPException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Psr\Http\Message\ResponseInterface;

class ResourceImagesControllerTest extends ControllerTestCase
{
    private const CONTROLLER = 'App\Controllers\ResourceImagesController';

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
            'active' => 1,
        ]);
        $this->resource->save();
    }

    public function tearDown(): void
    {
        unset($_FILES['resource_image']);
        $this->resource->gallery()->clear();
        parent::tearDown();
    }

    # The upload tests send a real file to the web container, that must be able to write in the upload directory:
    # sudo chown -R www-data:www-data public/assets/uploads
    public function test_upload_image(): void
    {
        $response = $this->upload('resource_image.jpg');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals("/resources/{$this->resource->id}", $response->getHeaderLine('Location'));

        $images = $this->resource->images()->get();
        $this->assertCount(1, $images);
        $this->assertInstanceOf(ResourceImage::class, $images[0]);
        $this->assertEquals('image/jpeg', $images[0]->mime_type);
        $this->assertMatchesRegularExpression(
            "#^resources/{$this->resource->id}/[0-9a-f]{16}\.jpg$#",
            $images[0]->file_path
        );
        $this->assertFileExists($images[0]->absolutePath());
    }

    public function test_upload_script_disguised_as_image_is_rejected(): void
    {
        $response = $this->upload('malicious.jpg');
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Não foi possível enviar a imagem! Por favor, verifique!', $body);
        $this->assertStringContainsString('O conteúdo do arquivo não é uma imagem JPEG ou PNG.', $body);
        $this->assertEmpty($this->resource->images()->get());
        $this->assertDirectoryDoesNotExist($this->resourceDirectory());
    }

    public function test_upload_without_file(): void
    {
        $response = $this->post(
            action: 'create',
            controllerName: self::CONTROLLER,
            params: ['resource_id' => (string) $this->resource->id]
        );

        $this->assertUploadRejected($response, 'Selecione uma imagem.');
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function malformedFilesProvider(): array
    {
        return [
            'not an array' => ['resource_image.jpg'],
            'multiple files' => [[
                'name' => ['a.jpg', 'b.jpg'],
                'tmp_name' => ['/tmp/a', '/tmp/b'],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size' => [10, 10],
            ]],
            'missing tmp_name' => [['name' => 'a.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 10]],
            'error as string' => [['name' => 'a.jpg', 'tmp_name' => '/tmp/a', 'error' => '0', 'size' => 10]],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedFilesProvider')]
    public function test_upload_with_malformed_file_is_rejected(mixed $file): void
    {
        $_FILES['resource_image'] = $file;

        $response = $this->post(
            action: 'create',
            controllerName: self::CONTROLLER,
            params: ['resource_id' => (string) $this->resource->id]
        );

        $this->assertUploadRejected($response, 'Selecione uma imagem.');
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
    public function test_upload_to_invalid_resource_is_not_found(string $id): void
    {
        try {
            $this->post(action: 'create', controllerName: self::CONTROLLER, params: ['resource_id' => $id]);
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Recurso não encontrado', $e->getMessage());
        }
    }

    public function test_destroy_image(): void
    {
        $image = $this->createImage($this->resource);

        $response = $this->post(
            action: 'destroy',
            controllerName: self::CONTROLLER,
            params: ['resource_id' => (string) $this->resource->id, 'id' => (string) $image->id]
        );

        $this->assertEquals("Location: /resources/{$this->resource->id}", $response);
        $this->assertEquals('Imagem removida com sucesso!', $_SESSION['flash']['success']);
        $this->assertNull(ResourceImage::findById($image->id));
        $this->assertFileDoesNotExist($image->absolutePath());
    }

    public function test_destroy_image_from_another_resource_is_not_found(): void
    {
        $otherResource = new Resource([
            'resource_category_id' => $this->resource->resource_category_id,
            'name' => 'Laboratório de Eletrônica',
            'active' => 1,
        ]);
        $otherResource->save();
        $image = $this->createImage($otherResource);

        $this->assertDestroyNotFound((string) $this->resource->id, (string) $image->id);

        $this->assertNotNull(ResourceImage::findById($image->id));
        $this->assertFileExists($image->absolutePath());

        $otherResource->gallery()->clear();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidImageIdsProvider(): array
    {
        return [
            'nonexistent' => ['999'],
            'not numeric' => ['abc'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidImageIdsProvider')]
    public function test_destroy_invalid_image_is_not_found(string $id): void
    {
        $this->assertDestroyNotFound((string) $this->resource->id, $id);
    }

    public function test_destroy_image_of_invalid_resource_is_not_found(): void
    {
        $image = $this->createImage($this->resource);

        try {
            $this->post(
                action: 'destroy',
                controllerName: self::CONTROLLER,
                params: ['resource_id' => '999', 'id' => (string) $image->id]
            );
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Recurso não encontrado', $e->getMessage());
        }

        $this->assertNotNull(ResourceImage::findById($image->id));
    }

    private function assertDestroyNotFound(string $resourceId, string $imageId): void
    {
        try {
            $this->post(
                action: 'destroy',
                controllerName: self::CONTROLLER,
                params: ['resource_id' => $resourceId, 'id' => $imageId]
            );
            $this->fail('Expected HTTPException');
        } catch (HTTPException $e) {
            $this->assertEquals(404, $e->getStatusCode());
            $this->assertEquals('Imagem não encontrada', $e->getMessage());
        }
    }

    private function createImage(Resource $resource): ResourceImage
    {
        /** @var ResourceImage $image */
        $image = $resource->images()->new([
            'file_path' => "resources/{$resource->id}/9f2c4be1a07d43e6.jpg",
            'mime_type' => 'image/jpeg',
        ]);
        $image->save();

        $directory = dirname($image->absolutePath());
        if (!is_dir($directory)) {
            mkdir(directory: $directory, recursive: true);
        }
        copy((string) Constants::rootPath()->join('tests/files/resource_image.jpg'), $image->absolutePath());

        return $image;
    }

    private function assertUploadRejected(string $response, string $error): void
    {
        $this->assertStringNotContainsString('Location:', $response);
        $this->assertMatchesRegularExpression('/<h1>\s*Laboratório de Redes\s*<\/h1>/', $response);
        $this->assertStringContainsString('is-invalid', $response);
        $this->assertStringContainsString($error, $response);
        $this->assertEmpty($this->resource->images()->get());
    }

    private function upload(string $fixture): ResponseInterface
    {
        $cookieJar = new CookieJar();
        $client = new Client([
            'allow_redirects' => false,
            'http_errors' => false,
            'base_uri' => 'http://web:8080'
        ]);

        $client->post('/login', [
            'form_params' => [
                'user[email]' => 'tecnico@example.com',
                'user[password]' => '123456'
            ],
            'cookies' => $cookieJar
        ]);

        $response = $client->post("/resources/{$this->resource->id}/images", [
            'multipart' => [
                [
                    'name' => 'resource_image',
                    'contents' => fopen((string) Constants::rootPath()->join("tests/files/{$fixture}"), 'r'),
                    'filename' => $fixture
                ]
            ],
            'cookies' => $cookieJar
        ]);

        if ($response->getStatusCode() === 500 && str_contains((string) $response->getBody(), 'Permission denied')) {
            $this->markTestSkipped('Upload path is not writable by the web container.');
        }

        return $response;
    }

    private function resourceDirectory(): string
    {
        return (string) Constants::rootPath()->join("public/assets/uploads/resources/{$this->resource->id}");
    }
}
