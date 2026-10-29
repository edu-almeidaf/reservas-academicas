<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\ResourceImage;
use Core\Constants\Constants;
use finfo;

class ResourceGallery
{
    private const ACCEPTED_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public function __construct(
        private Resource $resource
    ) {
    }

    /**
     * @param array{name: string, tmp_name: string, error: int, size: int} $file
     */
    public function upload(array $file): ResourceImage
    {
        /** @var ResourceImage $image */
        $image = $this->resource->images()->new();

        $error = $this->validationError($file);
        if ($error !== null) {
            $image->addError('image', $error);
            return $image;
        }

        $mimeType = $this->mimeType($file['tmp_name']);
        $fileName = bin2hex(random_bytes(8)) . '.' . ResourceImage::MIME_TYPES[$mimeType];

        $image->file_path = "resources/{$this->resource->id}/{$fileName}";
        $image->mime_type = $mimeType;

        if (!is_dir($this->directory())) {
            mkdir(directory: $this->directory(), recursive: true);
        }

        $this->moveFile($file['tmp_name'], $image->absolutePath());

        if (!$image->save()) {
            unlink($image->absolutePath());
        }

        return $image;
    }

    public function remove(ResourceImage $image): bool
    {
        if (!$image->destroy()) {
            return false;
        }

        if (is_file($image->absolutePath())) {
            unlink($image->absolutePath());
        }

        return true;
    }

    public function clear(): void
    {
        if (!is_dir($this->directory())) {
            return;
        }

        foreach (glob($this->directory() . '/*') ?: [] as $path) {
            unlink($path);
        }

        rmdir($this->directory());
    }

    protected function moveFile(string $from, string $to): bool
    {
        if (!move_uploaded_file($from, $to)) {
            $error = error_get_last();
            throw new \RuntimeException(
                'Failed to move uploaded file: ' . ($error['message'] ?? 'Unknown error')
            );
        }

        return true;
    }

    /**
     * @param array{name: string, tmp_name: string, error: int, size: int} $file
     */
    private function validationError(array $file): ?string
    {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return 'Selecione uma imagem.';
        }

        if (
            in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            || $file['size'] > ResourceImage::MAX_SIZE
        ) {
            return 'O arquivo deve ter no máximo 2 MB.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Não foi possível enviar o arquivo.';
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ACCEPTED_EXTENSIONS, true)) {
            return 'Formato inválido. Envie uma imagem JPEG ou PNG.';
        }

        // The MIME type comes from the file content, never from the type sent by the browser
        $mimeType = $this->mimeType($file['tmp_name']);
        if (!array_key_exists($mimeType, ResourceImage::MIME_TYPES) || getimagesize($file['tmp_name']) === false) {
            return 'O conteúdo do arquivo não é uma imagem JPEG ou PNG.';
        }

        return null;
    }

    private function mimeType(string $path): string
    {
        return (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
    }

    private function directory(): string
    {
        return (string) Constants::rootPath()->join("public/assets/uploads/resources/{$this->resource->id}");
    }
}
