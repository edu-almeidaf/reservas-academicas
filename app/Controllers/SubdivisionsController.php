<?php

namespace App\Controllers;

use App\Models\Resource;
use App\Models\ResourceImage;
use App\Models\Subdivision;
use Core\Exceptions\HTTPException;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class SubdivisionsController extends Controller
{
    private const SUBDIVISION_FIELDS = ['name'];

    public function index(Request $request): void
    {
        $resource = $this->findActiveResource($request);
        $subdivisions = $resource->activeSubdivisionOptions();

        $this->renderJson('subdivisions/index', compact('resource', 'subdivisions'));
    }

    public function create(Request $request): void
    {
        $resource = $this->findResource($request);

        /** @var Subdivision $subdivision */
        $subdivision = $resource->subdivisions()->new($this->subdivisionAttributes($request->getParam('subdivision')));

        if ($subdivision->save()) {
            FlashMessage::success('Subdivisão cadastrada com sucesso!');
            $this->redirectTo(route('resources.show', ['id' => $resource->id]));
            return;
        }

        FlashMessage::danger('Não foi possível cadastrar a subdivisão! Por favor, verifique!');
        /** @var ResourceImage $newImage */
        $newImage = $resource->images()->new();
        $this->render('resources/show', ResourcesController::galleryData($resource, $newImage, $subdivision));
    }

    public function destroy(Request $request): void
    {
        $resource = $this->findResource($request);
        $subdivision = $this->findSubdivision($resource, $request);

        if ($subdivision->hasReservations()) {
            FlashMessage::danger('Não é possível remover uma subdivisão que já foi usada em reservas.');
        } elseif ($subdivision->destroy()) {
            FlashMessage::success('Subdivisão removida com sucesso!');
        } else {
            FlashMessage::danger('Não foi possível remover a subdivisão.');
        }

        $this->redirectTo(route('resources.show', ['id' => $resource->id]));
    }

    /**
     * @return array<string, string>
     */
    private function subdivisionAttributes(mixed $params): array
    {
        if (!is_array($params)) {
            return [];
        }

        $attributes = [];
        foreach (self::SUBDIVISION_FIELDS as $field) {
            if (isset($params[$field]) && is_string($params[$field])) {
                $attributes[$field] = $params[$field];
            }
        }

        return $attributes;
    }

    private function findResource(Request $request): Resource
    {
        $id = $request->getParam('resource_id');
        $resource = is_string($id) && ctype_digit($id) ? Resource::findById((int) $id) : null;

        if ($resource === null) {
            throw new HTTPException('Recurso não encontrado', 404);
        }

        return $resource;
    }

    private function findSubdivision(Resource $resource, Request $request): Subdivision
    {
        $id = $request->getParam('id');
        $subdivision = is_string($id) && ctype_digit($id) ? $resource->subdivisions()->findById((int) $id) : null;

        if (!$subdivision instanceof Subdivision) {
            throw new HTTPException('Subdivisão não encontrada', 404);
        }

        return $subdivision;
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
