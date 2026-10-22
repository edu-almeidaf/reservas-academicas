<?php

namespace Database\Populate;

use App\Models\ResourceCategory;

class ResourceCategoriesPopulate
{
    public const CATEGORIES = ['Laboratório', 'Sala de aula', 'Auditório', 'Equipamento'];

    public static function populate(): void
    {
        foreach (self::CATEGORIES as $name) {
            $category = new ResourceCategory(['name' => $name]);
            $category->save();
        }

        $numberOfCategories = count(self::CATEGORIES);

        echo "Resource categories populated with $numberOfCategories registers\n";
    }
}
