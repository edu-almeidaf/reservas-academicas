<?php

namespace App\Models;

use App\Services\ResourceGallery;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * @property int $id
 * @property int $resource_category_id
 * @property string $name
 * @property string|null $description
 * @property int $active
 * @property ResourceCategory $category
 * @property ResourceImage[] $images
 */
class Resource extends Model
{
    protected static string $table = 'resources';
    protected static array $columns = ['resource_category_id', 'name', 'description', 'active'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'resource_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ResourceImage::class, 'resource_id');
    }

    public function gallery(): ResourceGallery
    {
        return new ResourceGallery($this);
    }

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::uniqueness('name', $this);

        Validations::notEmpty('resource_category_id', $this);
        Validations::existsIn('resource_category_id', ResourceCategory::class, $this);

        Validations::inclusion('active', [0, 1], $this);
    }

    public function isActive(): bool
    {
        return $this->active === 1;
    }

    public function statusLabel(): string
    {
        return $this->isActive() ? 'Ativo' : 'Inativo';
    }

    public function hasReservations(): bool
    {
        return Reservation::exists(['resource_id' => $this->id]);
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return self::namesById(self::all());
    }

    /**
     * @return array<int, string>
     */
    public static function activeOptions(): array
    {
        return self::namesById(self::where(['active' => 1]));
    }

    /**
     * @param array<Resource> $resources
     * @return array<int, string>
     */
    private static function namesById(array $resources): array
    {
        $options = [];
        foreach ($resources as $resource) {
            $options[$resource->id] = $resource->name;
        }
        asort($options);

        return $options;
    }
}
