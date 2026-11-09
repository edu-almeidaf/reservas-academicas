<?php

namespace Tests\Unit\Models\ResourceCategories;

use App\Models\Resource;
use App\Models\ResourceCategory;
use Tests\TestCase;

class ResourceCategoryTest extends TestCase
{
    private ResourceCategory $category;

    public function setUp(): void
    {
        parent::setUp();
        $this->category = new ResourceCategory(['name' => 'Laboratório']);
        $this->category->save();
    }

    public function test_should_create_new_category(): void
    {
        $this->assertCount(1, ResourceCategory::all());
    }

    public function test_errors_should_return_empty_name_error(): void
    {
        $category = new ResourceCategory(['name' => '']);

        $this->assertFalse($category->save());
        $this->assertEquals('não pode ser vazio!', $category->errors('name'));
    }

    public function test_errors_should_return_duplicated_name_error(): void
    {
        $category = new ResourceCategory(['name' => 'Laboratório']);

        $this->assertFalse($category->save());
        $this->assertEquals('já existe um registro com esse dado', $category->errors('name'));
    }

    public function test_resources_should_return_the_category_resources(): void
    {
        $other = new ResourceCategory(['name' => 'Auditório']);
        $other->save();

        $resource = new Resource([
            'resource_category_id' => $this->category->id,
            'name' => 'Laboratório 1',
            'active' => 1,
        ]);
        $resource->save();
        (new Resource(['resource_category_id' => $other->id, 'name' => 'Auditório 1', 'active' => 1]))->save();

        $resources = $this->category->resources()->get();

        $this->assertCount(1, $resources);
        $this->assertEquals($resource->id, $resources[0]->id);
    }

    public function test_options_should_return_names_ordered_by_name(): void
    {
        $other = new ResourceCategory(['name' => 'Auditório']);
        $other->save();

        $this->assertSame(
            [$other->id => 'Auditório', $this->category->id => 'Laboratório'],
            ResourceCategory::options()
        );
    }
}
