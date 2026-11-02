<?php

namespace App\Controllers;

use App\Models\Resource;
use Core\Exceptions\HTTPException;
use Core\Http\Controllers\Controller;
use Core\Http\Request;

class SubdivisionsController extends Controller
{
    public function index(Request $request): void
    {
        $resource = $this->findActiveResource($request);
        $subdivisions = $resource->activeSubdivisionOptions();

        $this->renderJson('subdivisions/index', compact('resource', 'subdivisions'));
    }

    private function findActiveResource(Request $request): Resource
    {
        $id = $request->getParam('resource_id');
        $resource = is_string($id) && ctype_digit($id) ? Resource::findById((int) $id) : null;

        if ($resource === null || !$resource->isActive()) {
            throw new HTTPException('Recurso não encontrado', 404);
        }

        return $resource;
    }
}
