<?php

namespace App\Models;

use Core\Database\ActiveRecord\BelongsTo;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\Model;
use Lib\Validations;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $advisor_id
 * @property int $resource_id
 * @property int $reservation_category_id
 * @property string $status
 * @property string $motive
 * @property string|null $justification
 * @property int|null $decided_by
 * @property string|null $decided_at
 * @property int|null $cancelled_by
 * @property string|null $cancelled_at
 * @property string|null $scheduled_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property User $user
 * @property Resource $resource
 * @property ReservationCategory $category
 * @property ReservationSchedule[] $schedules
 */
class Reservation extends Model
{
    public const STATUS_PENDING = 'pendente';
    public const STATUS_APPROVED = 'deferido';
    public const STATUS_REJECTED = 'indeferido';
    public const STATUS_CANCELLED = 'cancelado';

    private const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_APPROVED => 'Deferida',
        self::STATUS_REJECTED => 'Indeferida',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    private const SCHEDULE_FIELDS = ['scheduled_date', 'start_time', 'end_time'];

    protected static string $table = 'reservations';
    protected static array $columns = [
        'user_id',
        'advisor_id',
        'resource_id',
        'reservation_category_id',
        'status',
        'motive',
        'justification',
        'decided_by',
        'decided_at',
        'cancelled_by',
        'cancelled_at',
    ];

    /** @var array<string, string|null> */
    private array $schedule = [];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        if ($this->status === null) {
            $this->status = self::STATUS_PENDING;
        }
    }

    public function __get(string $property): mixed
    {
        if (in_array($property, self::SCHEDULE_FIELDS, true)) {
            return $this->schedule[$property] ?? null;
        }

        return parent::__get($property);
    }

    public function __set(string $property, mixed $value): void
    {
        if (in_array($property, self::SCHEDULE_FIELDS, true)) {
            $this->schedule[$property] = $value;
            return;
        }

        parent::__set($property, $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'reservation_category_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReservationSchedule::class, 'reservation_id');
    }

    public function validates(): void
    {
        Validations::notEmpty('user_id', $this);
        Validations::existsIn('user_id', User::class, $this);

        Validations::notEmpty('resource_id', $this);
        if ($this->errors('resource_id') === null && Validations::existsIn('resource_id', Resource::class, $this)) {
            $this->validatesActiveResource();
        }

        Validations::notEmpty('reservation_category_id', $this);
        Validations::existsIn('reservation_category_id', ReservationCategory::class, $this);

        Validations::notEmpty('motive', $this);
        Validations::inclusion('status', array_keys(self::STATUS_LABELS), $this);

        $this->validatesSchedule();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status];
    }

    public function loadSchedule(): void
    {
        $schedule = $this->currentSchedule();

        if ($schedule === null) {
            return;
        }

        $this->scheduled_date = $schedule->scheduled_date;
        $this->start_time = substr($schedule->start_time, 0, 5);
        $this->end_time = substr($schedule->end_time, 0, 5);
    }

    public function save(): bool
    {
        if (!parent::save()) {
            return false;
        }

        $schedule = $this->currentSchedule() ?? new ReservationSchedule(['reservation_id' => $this->id]);
        $schedule->scheduled_date = $this->scheduled_date;
        $schedule->start_time = $this->start_time;
        $schedule->end_time = $this->end_time;

        return $schedule->save();
    }

    private function currentSchedule(): ?ReservationSchedule
    {
        if ($this->newRecord()) {
            return null;
        }

        $schedules = $this->schedules()->get();
        $schedule = reset($schedules);

        return $schedule instanceof ReservationSchedule ? $schedule : null;
    }

    private function validatesActiveResource(): void
    {
        $resource = Resource::findById($this->resource_id);

        if ($resource !== null && !$resource->isActive()) {
            $this->addError('resource_id', 'não está disponível para reservas!');
        }
    }

    private function validatesSchedule(): void
    {
        if (Validations::notEmpty('scheduled_date', $this) && Validations::date('scheduled_date', $this)) {
            if ($this->scheduled_date < date('Y-m-d')) {
                $this->addError('scheduled_date', 'não pode ser no passado!');
            }
        }

        $validStart = Validations::notEmpty('start_time', $this) && Validations::time('start_time', $this);
        $validEnd = Validations::notEmpty('end_time', $this) && Validations::time('end_time', $this);

        if ($validStart && $validEnd && $this->end_time <= $this->start_time) {
            $this->addError('end_time', 'deve ser depois do horário de início!');
        }

        if ($this->hasValidScheduleFields() && $this->conflictsWithAnotherReservation()) {
            $this->addError('start_time', 'Horário indisponível para reserva.');
        }
    }

    private function hasValidScheduleFields(): bool
    {
        foreach (['resource_id', 'scheduled_date', 'start_time', 'end_time'] as $field) {
            if ($this->errors($field) !== null) {
                return false;
            }
        }

        return true;
    }

    private function conflictsWithAnotherReservation(): bool
    {
        return ReservationSchedule::hasConflict(
            $this->resource_id,
            $this->scheduled_date,
            $this->start_time,
            $this->end_time,
            $this->newRecord() ? null : $this->id
        );
    }
}
