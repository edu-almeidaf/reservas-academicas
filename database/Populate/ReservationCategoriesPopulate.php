<?php

namespace Database\Populate;

use App\Models\ReservationCategory;

class ReservationCategoriesPopulate
{
    public const CATEGORIES = ['Aula', 'Prova', 'Reunião', 'Evento', 'Pesquisa'];

    public static function populate(): void
    {
        foreach (self::CATEGORIES as $name) {
            $category = new ReservationCategory(['name' => $name]);
            $category->save();
        }

        $numberOfCategories = count(self::CATEGORIES);

        echo "Reservation categories populated with $numberOfCategories registers\n";
    }
}
