<?php

namespace App\Controllers;

use App\Models\Resource;
use App\Models\ResourceCategory;
use Core\Exceptions\HTTPException;
use Core\Http\Controllers\Controller;
use Core\Http\Request;

class ResourcesController extends Controller
{
    public function index(): void
    {
        $resources = Resource::all();
        $categories = ResourceCategory::options();

        $title = 'Recursos';
        $this->render('resources/index', compact('resources', 'categories', 'title'));
    }

    public function show(Request $request): void
    {
        $resource = $this->findResource($request);
        $category = $resource->category()->get();
        $images = $resource->images()->get();

        $title = $resource->name;
        $this->render('resources/show', compact('resource', 'category', 'images', 'title'));
    }

    private function findResource(Request $request): Resource
    {
        $id = $request->getParam('id');
        $resource = is_string($id) && ctype_digit($id) ? Resource::findById((int) $id) : null;

        if ($resource === null) {
            throw new HTTPException('Recurso não encontrado', 404);
        }

        return $resource;
    }
}
