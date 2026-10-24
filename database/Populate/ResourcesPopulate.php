<?php

namespace Database\Populate;

use App\Models\Resource;
use App\Models\ResourceCategory;

class ResourcesPopulate
{
    public static function populate(): void
    {
        $resources = [
            ['Laboratório', 'Laboratório de Informática 1', 'Laboratório com 40 computadores.', 1],
            ['Laboratório', 'Laboratório de Informática 2', 'Laboratório com 30 computadores.', 1],
            ['Laboratório', 'Laboratório de Redes', 'Bancadas com switches e roteadores.', 1],
            ['Laboratório', 'Laboratório de Eletrônica', 'Bancadas com osciloscópios.', 0],
            ['Sala de aula', 'Sala de Aula 101', 'Sala com 40 carteiras e projetor.', 1],
            ['Sala de aula', 'Sala de Aula 102', 'Sala com 40 carteiras e projetor.', 1],
            ['Sala de aula', 'Sala de Aula 201', 'Sala com 60 carteiras.', 1],
            ['Auditório', 'Auditório Central', 'Auditório com 200 lugares.', 1],
            ['Auditório', 'Miniauditório', 'Auditório com 80 lugares.', 1],
            ['Equipamento', 'Projetor Multimídia 1', 'Projetor portátil com cabo HDMI.', 1],
            ['Equipamento', 'Projetor Multimídia 2', 'Projetor portátil com cabo HDMI.', 0],
            ['Equipamento', 'Notebook de Apresentação', 'Notebook para apresentações.', 1],
        ];

        foreach ($resources as [$category, $name, $description, $active]) {
            $resource = new Resource([
                'resource_category_id' => ResourceCategory::findBy(['name' => $category])->id,
                'name' => $name,
                'description' => $description,
                'active' => $active,
            ]);
            $resource->save();
        }

        $numberOfResources = count($resources);

        echo "Resources populated with $numberOfResources registers\n";
    }
}
