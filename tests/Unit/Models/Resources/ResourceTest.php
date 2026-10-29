<?php

namespace Tests\Unit\Models\Resources;

use App\Models\Reservation;
use App\Models\ReservationCategory;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceImage;
use App\Models\User;
use App\Services\ResourceGallery;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    private ResourceCategory $category;
    private Resource $resource;

    public function setUp(): void
    {
        parent::setUp();
        $this->category = new ResourceCategory(['name' => 'Laboratório']);
        $this->category->save();

        $this->resource = $this->newResource(['name' => 'Laboratório de Redes']);
        $this->resource->save();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function newResource(array $overrides = []): Resource
    {
        return new Resource(array_merge([
            'resource_category_id' => $this->category->id,
            'name' => 'Laboratório 1',
            'description' => 'Laboratório com 40 computadores.',
            'active' => 1,
        ], $overrides));
    }

    public function test_should_create_new_resource(): void
    {
        $this->assertCount(1, Resource::all());
    }

    public function test_find_by_id_should_return_integer_columns(): void
    {
        $resource = Resource::findById($this->resource->id);

        $this->assertNotNull($resource);
        $this->assertSame($this->category->id, $resource->resource_category_id);
        $this->assertSame(1, $resource->active);
    }

    public function test_category_should_return_the_resource_category(): void
    {
        $this->assertEquals($this->category->id, $this->resource->category->id);
        $this->assertEquals('Laboratório', $this->resource->category->name);
    }

    public function test_is_active(): void
    {
        $this->assertTrue($this->resource->isActive());
        $this->assertFalse($this->newResource(['active' => 0])->isActive());
    }

    public function test_errors_should_return_empty_name_error(): void
    {
        $resource = $this->newResource(['name' => '']);

        $this->assertFalse($resource->save());
        $this->assertEquals('não pode ser vazio!', $resource->errors('name'));
    }

    public function test_errors_should_return_duplicated_name_error(): void
    {
        $resource = $this->newResource(['name' => 'Laboratório de Redes']);

        $this->assertFalse($resource->save());
        $this->assertEquals('já existe um registro com esse dado', $resource->errors('name'));
    }

    public function test_update_should_keep_the_same_name(): void
    {
        $this->resource->description = 'Nova descrição.';

        $this->assertTrue($this->resource->save());
    }

    public function test_errors_should_return_empty_category_error(): void
    {
        $resource = $this->newResource(['resource_category_id' => null]);

        $this->assertFalse($resource->save());
        $this->assertEquals('não pode ser vazio!', $resource->errors('resource_category_id'));
    }

    public function test_errors_should_return_nonexistent_category_error(): void
    {
        $resource = $this->newResource(['resource_category_id' => 999]);

        $this->assertFalse($resource->save());
        $this->assertEquals('não é um valor válido!', $resource->errors('resource_category_id'));
    }

    public function test_errors_should_return_invalid_active_error(): void
    {
        $resource = $this->newResource(['active' => 2]);

        $this->assertFalse($resource->save());
        $this->assertEquals('não é um valor válido!', $resource->errors('active'));
    }

    public function test_options_should_return_all_names_ordered_by_name(): void
    {
        $inactive = $this->newResource(['name' => 'Auditório', 'active' => 0]);
        $inactive->save();

        $this->assertSame(
            [$inactive->id => 'Auditório', $this->resource->id => 'Laboratório de Redes'],
            Resource::options()
        );
    }

    public function test_active_options_should_not_return_inactive_resources(): void
    {
        $inactive = $this->newResource(['name' => 'Auditório', 'active' => 0]);
        $inactive->save();

        $this->assertSame([$this->resource->id => 'Laboratório de Redes'], Resource::activeOptions());
    }

    public function test_images_should_return_only_the_resource_images(): void
    {
        $other = $this->newResource(['name' => 'Auditório']);
        $other->save();

        $image = $this->resource->images()->new([
            'file_path' => "resources/{$this->resource->id}/image.jpg",
            'mime_type' => 'image/jpeg',
        ]);
        $image->save();
        $other->images()->new(['file_path' => "resources/{$other->id}/image.png", 'mime_type' => 'image/png'])->save();

        $images = $this->resource->images;

        $this->assertCount(1, $images);
        $this->assertEquals($image->id, $images[0]->id);
        $this->assertInstanceOf(ResourceImage::class, $images[0]);
    }

    public function test_status_label(): void
    {
        $this->assertSame('Ativo', $this->resource->statusLabel());
        $this->assertSame('Inativo', $this->newResource(['active' => 0])->statusLabel());
    }

    public function test_gallery_should_return_the_resource_gallery(): void
    {
        $this->assertInstanceOf(ResourceGallery::class, $this->resource->gallery());
    }

    public function test_has_reservations(): void
    {
        $this->assertFalse($this->resource->hasReservations());

        $user = new User([
            'profile' => User::PROFILE_STUDENT,
            'name' => 'User',
            'email' => 'discente@example.com',
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);
        $user->save();
        $category = new ReservationCategory(['name' => 'Aula']);
        $category->save();

        $reservation = new Reservation([
            'user_id' => $user->id,
            'resource_id' => $this->resource->id,
            'reservation_category_id' => $category->id,
            'motive' => 'Aula de Redes',
            'scheduled_date' => date('Y-m-d', strtotime('+1 day')),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);
        $this->assertTrue($reservation->save());

        $this->assertTrue($this->resource->hasReservations());
    }
}
