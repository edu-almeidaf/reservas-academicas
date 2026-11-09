<?php

namespace Tests\Unit\Models\ResourceImages;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceImage;
use Core\Constants\Constants;
use Core\Database\Database;
use PDOException;
use Tests\TestCase;

class ResourceImageTest extends TestCase
{
    private Resource $resource;
    private ResourceImage $image;

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

        $this->image = $this->newImage();
        $this->image->save();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function newImage(array $overrides = []): ResourceImage
    {
        return new ResourceImage(array_merge([
            'resource_id' => $this->resource->id,
            'file_path' => "resources/{$this->resource->id}/9f2c4be1a07d43e6.jpg",
            'mime_type' => 'image/jpeg',
        ], $overrides));
    }

    public function test_should_create_new_image(): void
    {
        $this->assertCount(1, ResourceImage::all());
    }

    public function test_should_accept_png(): void
    {
        $this->assertTrue($this->newImage(['mime_type' => 'image/png'])->save());
    }

    public function test_resource_should_return_the_image_resource(): void
    {
        $this->assertEquals($this->resource->id, $this->image->resource->id);
        $this->assertEquals('Laboratório de Redes', $this->image->resource->name);
    }

    public function test_errors_should_return_empty_resource_error(): void
    {
        $image = $this->newImage(['resource_id' => null]);

        $this->assertFalse($image->save());
        $this->assertEquals('não pode ser vazio!', $image->errors('resource_id'));
    }

    public function test_errors_should_return_nonexistent_resource_error(): void
    {
        $image = $this->newImage(['resource_id' => 999]);

        $this->assertFalse($image->save());
        $this->assertEquals('não é um valor válido!', $image->errors('resource_id'));
    }

    public function test_errors_should_return_empty_file_path_error(): void
    {
        $image = $this->newImage(['file_path' => '']);

        $this->assertFalse($image->save());
        $this->assertEquals('não pode ser vazio!', $image->errors('file_path'));
    }

    public function test_errors_should_return_empty_mime_type_error(): void
    {
        $image = $this->newImage(['mime_type' => '']);

        $this->assertFalse($image->save());
        $this->assertEquals('não pode ser vazio!', $image->errors('mime_type'));
    }

    public function test_errors_should_return_invalid_mime_type_error(): void
    {
        $image = $this->newImage(['mime_type' => 'text/x-php']);

        $this->assertFalse($image->save());
        $this->assertEquals('não é um valor válido!', $image->errors('mime_type'));
    }

    public function test_url_should_return_public_path(): void
    {
        $this->assertSame(
            "/assets/uploads/resources/{$this->resource->id}/9f2c4be1a07d43e6.jpg",
            $this->image->url()
        );
    }

    public function test_absolute_path_should_point_to_public_uploads_folder(): void
    {
        $this->assertSame(
            Constants::rootPath() . "/public/assets/uploads/resources/{$this->resource->id}/9f2c4be1a07d43e6.jpg",
            $this->image->absolutePath()
        );
    }

    public function test_accepted_mime_types(): void
    {
        $this->assertSame(['image/jpeg', 'image/png'], ResourceImage::acceptedMimeTypes());
    }

    public function test_database_should_reject_image_for_nonexistent_resource(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionCode('23000');

        Database::getDatabaseConn()->exec(
            "INSERT INTO resource_images (resource_id, file_path, mime_type)
             VALUES (999, 'resources/999/image.jpg', 'image/jpeg')"
        );
    }

    public function test_deleting_resource_should_cascade_images(): void
    {
        $this->resource->destroy();

        $this->assertSame([], ResourceImage::where(['resource_id' => $this->resource->id]));
    }
}
