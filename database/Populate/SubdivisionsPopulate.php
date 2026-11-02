<?php

namespace Database\Populate;

use App\Models\Resource;
use App\Models\Subdivision;

class SubdivisionsPopulate
{
    public static function populate(): void
    {
        $subdivisions = [
            ['Laboratório de Informática 1', 'Bancada 1', 1],
            ['Laboratório de Informática 1', 'Bancada 2', 1],
            ['Laboratório de Informática 1', 'Bancada 3', 1],
            ['Laboratório de Informática 1', 'Bancada 4', 0],
            ['Laboratório de Redes', 'Bancada A', 1],
            ['Laboratório de Redes', 'Bancada B', 1],
            ['Laboratório de Redes', 'Rack de servidores', 1],
            ['Auditório Central', 'Palco', 1],
            ['Auditório Central', 'Plateia', 1],
        ];

        foreach ($subdivisions as [$resource, $name, $active]) {
            $subdivision = new Subdivision([
                'resource_id' => Resource::findBy(['name' => $resource])->id,
                'name' => $name,
                'active' => $active,
            ]);
            $subdivision->save();
        }

        $numberOfSubdivisions = count($subdivisions);

        echo "Subdivisions populated with $numberOfSubdivisions registers\n";
    }
}
