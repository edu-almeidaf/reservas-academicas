<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use Lib\Validations;
use PDO;

/**
 * @property int $id
 * @property int $reservation_id
 * @property string $scheduled_date
 * @property string $start_time
 * @property string $end_time
 * @property Reservation $reservation
 */
class ReservationSchedule extends Model
{
    protected static string $table = 'reservation_schedules';
    protected static array $columns = ['reservation_id', 'scheduled_date', 'start_time', 'end_time'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function validates(): void
    {
        Validations::notEmpty('reservation_id', $this);
        Validations::notEmpty('scheduled_date', $this);
        Validations::notEmpty('start_time', $this);
        Validations::notEmpty('end_time', $this);
    }

    public static function hasConflict(
        int $resourceId,
        string $date,
        string $start,
        string $end,
        ?int $exceptReservationId = null
    ): bool {
        $sql = <<<SQL
            SELECT COUNT(*) FROM reservation_schedules s
            JOIN reservations r ON r.id = s.reservation_id
            WHERE r.resource_id = :resource_id
              AND r.status IN (:pending, :approved)
              AND s.scheduled_date = :scheduled_date
              AND s.start_time < :end_time
              AND s.end_time > :start_time
        SQL;

        if ($exceptReservationId !== null) {
            $sql .= ' AND r.id <> :except_id';
        }

        $stmt = Database::getDatabaseConn()->prepare($sql);
        $stmt->bindValue('resource_id', $resourceId, PDO::PARAM_INT);
        $stmt->bindValue('pending', Reservation::STATUS_PENDING);
        $stmt->bindValue('approved', Reservation::STATUS_APPROVED);
        $stmt->bindValue('scheduled_date', $date);
        $stmt->bindValue('start_time', $start);
        $stmt->bindValue('end_time', $end);

        if ($exceptReservationId !== null) {
            $stmt->bindValue('except_id', $exceptReservationId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param int[] $reservationIds
     * @return array<int, ReservationSchedule>
     */
    public static function forReservations(array $reservationIds): array
    {
        if (empty($reservationIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($reservationIds), '?'));
        $attributes = implode(', ', self::$columns);

        $sql = <<<SQL
            SELECT id, {$attributes} FROM reservation_schedules WHERE reservation_id IN ({$placeholders});
        SQL;

        $stmt = Database::getDatabaseConn()->prepare($sql);
        $stmt->execute(array_values($reservationIds));

        $schedules = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $schedules[$row['reservation_id']] ??= new self($row);
        }

        return $schedules;
    }
}
