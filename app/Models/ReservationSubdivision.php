<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
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

    /**
     * Subdivision names of each reservation in a single query, to list a page without one query per row
     *
     * @param int[] $reservationIds
     * @return array<int, string[]> reservation id => subdivision names ordered by name
     */
    public static function namesForReservations(array $reservationIds): array
    {
        if (empty($reservationIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($reservationIds), '?'));

        $sql = <<<SQL
            SELECT rs.reservation_id, s.name FROM reservation_subdivision rs
            JOIN subdivisions s ON s.id = rs.subdivision_id
            WHERE rs.reservation_id IN ({$placeholders})
            ORDER BY s.name;
        SQL;

        $stmt = Database::getDatabaseConn()->prepare($sql);
        $stmt->execute(array_values($reservationIds));

        $names = [];
        foreach ($stmt->fetchAll() as $row) {
            $names[(int) $row['reservation_id']][] = $row['name'];
        }

        return $names;
    }
}
