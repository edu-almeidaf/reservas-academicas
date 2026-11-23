<?php

$subdivisionsToJson = [];

foreach ($subdivisions as $id => $name) {
    $subdivisionsToJson[] = ['id' => $id, 'name' => $name];
}

$json['resource'] = ['id' => $resource->id, 'name' => $resource->name];
$json['subdivisions'] = $subdivisionsToJson;
