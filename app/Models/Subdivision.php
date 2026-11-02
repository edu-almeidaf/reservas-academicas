<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\BelongsToMany;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * @property int $id
 * @property int $resource_id
 * @property string $name
 * @property int $active
 * @property Resource $resource
 * @property Reservation[] $reservations
 */
class Subdivision extends Model
{
    protected static string $table = 'subdivisions';
    protected static array $columns = ['resource_id', 'name', 'active'];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        if ($this->active === null) {
            $this->active = 1;
        }
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'reservation_subdivision', 'subdivision_id', 'reservation_id');
    }

    public function validates(): void
    {
        Validations::notEmpty('resource_id', $this);
        Validations::existsIn('resource_id', Resource::class, $this);

        if (Validations::notEmpty('name', $this) && $this->errors('resource_id') === null) {
            Validations::uniqueness(['resource_id', 'name'], $this);
        }

        Validations::inclusion('active', [0, 1], $this);
    }

    public function isActive(): bool
    {
        return $this->active === 1;
    }

    public function statusLabel(): string
    {
        return $this->isActive() ? 'Ativa' : 'Inativa';
    }

    public function hasReservations(): bool
    {
        return ReservationSubdivision::exists(['subdivision_id' => $this->id]);
    }
}
