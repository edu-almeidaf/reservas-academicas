<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * @property int $id
 * @property string $name
 */
class ReservationCategory extends Model
{
    protected static string $table = 'reservation_categories';
    protected static array $columns = ['name'];

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
