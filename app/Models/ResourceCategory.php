<?php

namespace App\Models;

use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * @property int $id
 * @property string $name
 * @property Resource[] $resources
 */
class ResourceCategory extends Model
{
    protected static string $table = 'resource_categories';
    protected static array $columns = ['name'];

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'resource_category_id');
    }

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::uniqueness('name', $this);
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $category) {
            $options[$category->id] = $category->name;
        }
        asort($options);

        return $options;
    }
}
