<?php

namespace App\Controllers;

use App\Models\Resource;
use App\Models\ResourceImage;
use Core\Exceptions\HTTPException;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class ResourceImagesController extends Controller
{
    private const UPLOAD_FIELD = 'resource_image';

    public function create(Request $request): void
    {
        $resource = $this->findResource($request);
        $file = $this->uploadedFile();

        if ($file === null) {
            /** @var ResourceImage $image */
            $image = $resource->images()->new();
            $image->addError('image', 'Selecione uma imagem.');
        } else {
            $image = $resource->gallery()->upload($file);
        }

        if (!$image->newRecord()) {
            FlashMessage::success('Imagem enviada com sucesso!');
            $this->redirectTo(route('resources.show', ['id' => $resource->id]));
            return;
        }

        FlashMessage::danger('Não foi possível enviar a imagem! Por favor, verifique!');
        $this->render('resources/show', ResourcesController::galleryData($resource, $image));
    }

    public function destroy(Request $request): void
    {
        $resource = $this->findResource($request);
        $image = $this->findImage($resource, $request);

        if ($resource->gallery()->remove($image)) {
            FlashMessage::success('Imagem removida com sucesso!');
        } else {
            FlashMessage::danger('Não foi possível remover a imagem.');
        }

        $this->redirectTo(route('resources.show', ['id' => $resource->id]));
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

    private function findImage(Resource $resource, Request $request): ResourceImage
    {
        $id = $request->getParam('id');
        $image = is_string($id) && ctype_digit($id) ? $resource->images()->findById((int) $id) : null;

        if (!$image instanceof ResourceImage) {
            throw new HTTPException('Imagem não encontrada', 404);
        }

        return $image;
    }

    /**
     * @return array{name: string, tmp_name: string, error: int, size: int}|null
     */
    private function uploadedFile(): ?array
    {
        if (!isset($_FILES[self::UPLOAD_FIELD]) || !is_array($_FILES[self::UPLOAD_FIELD])) {
            return null;
        }

        $file = $_FILES[self::UPLOAD_FIELD];

        if (
            !isset($file['name'], $file['tmp_name'], $file['error'], $file['size'])
            || !is_string($file['name'])
            || !is_string($file['tmp_name'])
            || !is_int($file['error'])
            || !is_int($file['size'])
        ) {
            return null;
        }

        return [
            'name' => $file['name'],
            'tmp_name' => $file['tmp_name'],
            'error' => $file['error'],
            'size' => $file['size'],
        ];
    }
}
