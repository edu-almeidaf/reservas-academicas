<?php

namespace App\Controllers;

use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\ResourceImage;
use Core\Exceptions\HTTPException;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

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
        /** @var ResourceImage $newImage */
        $newImage = $resource->images()->new();

        $this->render('resources/show', self::galleryData($resource, $newImage));
    }

    public function destroy(Request $request): void
    {
        $resource = $this->findResource($request);

        if ($resource->hasReservations()) {
            FlashMessage::danger('Não é possível remover um recurso que já foi usado em reservas.');
            $this->redirectTo(route('resources.show', ['id' => $resource->id]));
            return;
        }

        if ($resource->destroy()) {
            $resource->gallery()->clear();
            FlashMessage::success('Recurso removido com sucesso!');
        } else {
            FlashMessage::danger('Não foi possível remover o recurso.');
        }

        $this->redirectTo(route('resources.index'));
    }

    /**
     * Data of the gallery page, shared with the upload that renders it again with the errors
     *
     * @return array<string, mixed>
     */
    public static function galleryData(Resource $resource, ResourceImage $newImage): array
    {
        $category = $resource->category()->get();
        $images = $resource->images()->get();
        $acceptedMimeTypes = ResourceImage::acceptedMimeTypes();

        $title = $resource->name;
        return compact('resource', 'category', 'images', 'newImage', 'acceptedMimeTypes', 'title');
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
