<?php

namespace App\Models;

use Core\Constants\Constants;
use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * @property int $id
 * @property int $resource_id
 * @property string $file_path
 * @property string $mime_type
 * @property Resource $resource
 */
class ResourceImage extends Model
{
    public const MAX_SIZE = 2 * 1024 * 1024;
    public const MIME_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

    private const UPLOADS_DIR = '/assets/uploads/';

    protected static string $table = 'resource_images';
    protected static array $columns = ['resource_id', 'file_path', 'mime_type'];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    public function validates(): void
    {
        Validations::notEmpty('resource_id', $this);
        Validations::existsIn('resource_id', Resource::class, $this);

        Validations::notEmpty('file_path', $this);

        if (Validations::notEmpty('mime_type', $this)) {
            Validations::inclusion('mime_type', self::acceptedMimeTypes(), $this);
        }
    }

    public function url(): string
    {
        return self::UPLOADS_DIR . $this->file_path;
    }

    public function absolutePath(): string
    {
        return (string) Constants::rootPath()->join('public' . self::UPLOADS_DIR . $this->file_path);
    }

    /**
     * @return array<int, string>
     */
    public static function acceptedMimeTypes(): array
    {
        return array_keys(self::MIME_TYPES);
    }
}
