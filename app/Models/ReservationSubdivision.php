<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * @property int $id
 * @property int $reservation_id
 * @property int $subdivision_id
 * @property Reservation $reservation
 * @property Subdivision $subdivision
 */
class ReservationSubdivision extends Model
{
    protected static string $table = 'reservation_subdivision';
    protected static array $columns = ['reservation_id', 'subdivision_id'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id');
    }

    public function validates(): void
    {
        Validations::notEmpty('reservation_id', $this);
        Validations::existsIn('reservation_id', Reservation::class, $this);

        Validations::notEmpty('subdivision_id', $this);
        Validations::existsIn('subdivision_id', Subdivision::class, $this);

        if ($this->errors('reservation_id') === null && $this->errors('subdivision_id') === null) {
            Validations::uniqueness(['reservation_id', 'subdivision_id'], $this);
        }
    }
}
