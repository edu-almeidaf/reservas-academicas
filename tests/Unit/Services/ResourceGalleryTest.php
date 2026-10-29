<?php

namespace Tests\Unit\Services;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceImage;
use App\Services\ResourceGallery;
use Core\Constants\Constants;
use Tests\TestCase;

class ResourceGalleryTest extends TestCase
{
    private Resource $resource;
    private ResourceGallery $gallery;

    public function setUp(): void
    {
        parent::setUp();
        $category = new ResourceCategory(['name' => 'Laboratório']);
        $category->save();

        $this->resource = new Resource([
            'resource_category_id' => $category->id,
            'name' => 'Laboratório de Redes',
            'active' => 1,
        ]);
        $this->resource->save();

        // move_uploaded_file only accepts files sent by an HTTP upload, so the move is replaced by a copy
        $this->gallery = $this->getMockBuilder(ResourceGallery::class)
            ->setConstructorArgs([$this->resource])
            ->onlyMethods(['moveFile'])
            ->getMock();
        $this->gallery->method('moveFile')->willReturnCallback(fn (string $from, string $to) => copy($from, $to));
    }

    public function tearDown(): void
    {
        $this->resource->gallery()->clear();
        parent::tearDown();
    }

    /**
     * @return array{name: string, tmp_name: string, error: int, size: int}
     */
    private function uploadedFile(string $fixture, ?string $name = null): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'php');
        copy(Constants::rootPath()->join("tests/files/{$fixture}"), $tmpFile);

        return [
            'name' => $name ?? $fixture,
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($tmpFile),
        ];
    }

    private function resourceDirectory(): string
    {
        return Constants::rootPath()->join("public/assets/uploads/resources/{$this->resource->id}");
    }

    private function assertRejected(ResourceImage $image, string $message): void
    {
        $this->assertTrue($image->newRecord());
        $this->assertEquals($message, $image->errors('image'));
        $this->assertCount(0, ResourceImage::all());
        $this->assertDirectoryDoesNotExist($this->resourceDirectory());
    }

    public function test_upload_jpeg(): void
    {
        $image = $this->gallery->upload($this->uploadedFile('resource_image.jpg'));

        $this->assertFalse($image->newRecord());
        $this->assertSame('image/jpeg', $image->mime_type);
        $this->assertSame($this->resource->id, $image->resource_id);
        $this->assertMatchesRegularExpression(
            "#^resources/{$this->resource->id}/[0-9a-f]{16}\.jpg$#",
            $image->file_path
        );
        $this->assertFileExists($image->absolutePath());
    }

    public function test_upload_png(): void
    {
        $image = $this->gallery->upload($this->uploadedFile('resource_image.png'));

        $this->assertFalse($image->newRecord());
        $this->assertSame('image/png', $image->mime_type);
        $this->assertStringEndsWith('.png', $image->file_path);
        $this->assertFileExists($image->absolutePath());
    }

    public function test_upload_generates_random_file_name(): void
    {
        $first = $this->gallery->upload($this->uploadedFile('resource_image.jpg', 'foto.jpeg'));
        $second = $this->gallery->upload($this->uploadedFile('resource_image.jpg', 'foto.jpeg'));

        $this->assertStringNotContainsString('foto', $first->file_path);
        $this->assertStringEndsWith('.jpg', $first->file_path);
        $this->assertNotEquals($first->file_path, $second->file_path);
        $this->assertCount(2, $this->resource->images()->get());
    }

    public function test_upload_without_file(): void
    {
        $file = ['name' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0];

        $this->assertRejected($this->gallery->upload($file), 'Selecione uma imagem.');
    }

    public function test_upload_too_large(): void
    {
        $file = $this->uploadedFile('resource_image.jpg');
        $file['size'] = ResourceImage::MAX_SIZE + 1;

        $this->assertRejected($this->gallery->upload($file), 'O arquivo deve ter no máximo 2 MB.');
    }

    public function test_upload_larger_than_server_limit(): void
    {
        $file = ['name' => 'foto.jpg', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0];

        $this->assertRejected($this->gallery->upload($file), 'O arquivo deve ter no máximo 2 MB.');
    }

    public function test_upload_with_upload_error(): void
    {
        $file = $this->uploadedFile('resource_image.jpg');
        $file['error'] = UPLOAD_ERR_PARTIAL;

        $this->assertRejected($this->gallery->upload($file), 'Não foi possível enviar o arquivo.');
    }

    public function test_upload_invalid_extension(): void
    {
        $this->assertRejected(
            $this->gallery->upload($this->uploadedFile('malicious.php')),
            'Formato inválido. Envie uma imagem JPEG ou PNG.'
        );
    }

    public function test_upload_image_with_invalid_extension(): void
    {
        $this->assertRejected(
            $this->gallery->upload($this->uploadedFile('resource_image.jpg', 'foto.gif')),
            'Formato inválido. Envie uma imagem JPEG ou PNG.'
        );
    }

    public function test_upload_script_disguised_as_image(): void
    {
        $this->assertRejected(
            $this->gallery->upload($this->uploadedFile('malicious.jpg')),
            'O conteúdo do arquivo não é uma imagem JPEG ou PNG.'
        );
    }

    public function test_upload_does_not_use_client_mime_type(): void
    {
        $file = $this->uploadedFile('malicious.jpg') + ['type' => 'image/jpeg'];

        $this->assertRejected(
            $this->gallery->upload($file),
            'O conteúdo do arquivo não é uma imagem JPEG ou PNG.'
        );
    }

    public function test_remove_deletes_record_and_file(): void
    {
        $image = $this->gallery->upload($this->uploadedFile('resource_image.jpg'));

        $this->assertTrue($this->gallery->remove($image));

        $this->assertNull(ResourceImage::findById($image->id));
        $this->assertFileDoesNotExist($image->absolutePath());
    }

    public function test_clear_deletes_resource_folder(): void
    {
        $this->gallery->upload($this->uploadedFile('resource_image.jpg'));
        $this->gallery->upload($this->uploadedFile('resource_image.png'));

        $this->gallery->clear();

        $this->assertDirectoryDoesNotExist($this->resourceDirectory());
    }

    public function test_clear_without_folder_does_nothing(): void
    {
        $this->gallery->clear();

        $this->assertDirectoryDoesNotExist($this->resourceDirectory());
    }
}
