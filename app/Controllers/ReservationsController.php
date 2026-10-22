<?php

namespace App\Controllers;

use App\Models\Reservation;
use App\Models\ReservationCategory;
use App\Models\ReservationSchedule;
use App\Models\Resource;
use Core\Exceptions\HTTPException;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class ReservationsController extends Controller
{
    private const PER_PAGE = 10;
    private const RESERVATION_TEXT_FIELDS = ['motive', 'scheduled_date', 'start_time', 'end_time'];
    private const RESERVATION_INTEGER_FIELDS = ['resource_id', 'reservation_category_id'];

    public function index(Request $request): void
    {
        $paginator = $this->current_user->reservations()->paginate(
            page: $this->page($request),
            per_page: self::PER_PAGE,
            route: 'reservations.paginate'
        );
        $reservations = $paginator->registers();
        $schedules = ReservationSchedule::forReservations(
            array_map(fn ($reservation) => $reservation->id, $reservations)
        );
        $resources = Resource::options();

        $title = 'Minhas Reservas';
        $this->render('reservations/index', compact('paginator', 'reservations', 'schedules', 'resources', 'title'));
    }

    public function show(Request $request): void
    {
        $reservation = $this->findReservation($request);

        $title = "Reserva #{$reservation->id}";
        $this->render('reservations/show', compact('reservation', 'title'));
    }

    public function new(): void
    {
        $reservation = $this->newReservation([]);

        $this->renderForm('reservations/new', $reservation, 'Nova Reserva');
    }

    public function create(Request $request): void
    {
        $reservation = $this->newReservation($this->reservationAttributes($request->getParam('reservation')));

        if ($reservation->save()) {
            FlashMessage::success('Reserva solicitada com sucesso!');
            $this->redirectTo(route('reservations.index'));
        } else {
            FlashMessage::danger('Existem dados incorretos! Por favor, verifique!');
            $this->renderForm('reservations/new', $reservation, 'Nova Reserva');
        }
    }

    public function edit(Request $request): void
    {
        $reservation = $this->findReservation($request);

        if (!$reservation->isPending()) {
            $this->denyChange($reservation);
            return;
        }

        $this->renderForm('reservations/edit', $reservation, "Editar Reserva #{$reservation->id}");
    }

    public function update(Request $request): void
    {
        $reservation = $this->findReservation($request);

        if (!$reservation->isPending()) {
            $this->denyChange($reservation);
            return;
        }

        foreach ($this->reservationAttributes($request->getParam('reservation')) as $attribute => $value) {
            $reservation->$attribute = $value;
        }

        if ($reservation->save()) {
            FlashMessage::success('Reserva atualizada com sucesso!');
            $this->redirectTo(route('reservations.index'));
        } else {
            FlashMessage::danger('Existem dados incorretos! Por favor, verifique!');
            $this->renderForm('reservations/edit', $reservation, "Editar Reserva #{$reservation->id}");
        }
    }

    private function denyChange(Reservation $reservation): void
    {
        FlashMessage::danger('Somente reservas pendentes podem ser alteradas.');
        $this->redirectTo(route('reservations.show', ['id' => $reservation->id]));
    }

    /**
     * @param array<string, string|int> $attributes
     */
    private function newReservation(array $attributes): Reservation
    {
        /** @var Reservation $reservation */
        $reservation = $this->current_user->reservations()->new($attributes);

        return $reservation;
    }

    private function renderForm(string $view, Reservation $reservation, string $title): void
    {
        $resources = Resource::activeOptions();
        $categories = ReservationCategory::options();
        $this->render($view, compact('reservation', 'resources', 'categories', 'title'));
    }

    /**
     * @return array<string, string|int>
     */
    private function reservationAttributes(mixed $params): array
    {
        if (!is_array($params)) {
            return [];
        }

        $attributes = [];
        foreach (self::RESERVATION_TEXT_FIELDS as $field) {
            if (isset($params[$field]) && is_string($params[$field])) {
                $attributes[$field] = $params[$field];
            }
        }

        foreach (self::RESERVATION_INTEGER_FIELDS as $field) {
            if (isset($params[$field]) && is_string($params[$field]) && ctype_digit($params[$field])) {
                $attributes[$field] = (int) $params[$field];
            }
        }

        return $attributes;
    }

    private function findReservation(Request $request): Reservation
    {
        $id = $request->getParam('id');
        $reservation = is_string($id) && ctype_digit($id)
            ? $this->current_user->reservations()->findById((int) $id)
            : null;

        if (!$reservation instanceof Reservation) {
            throw new HTTPException('Reserva não encontrada', 404);
        }

        $reservation->loadSchedule();

        return $reservation;
    }

    private function page(Request $request): int
    {
        $page = $request->getParam('page', '1');

        if (!is_string($page) || !ctype_digit($page) || (int) $page < 1) {
            throw new HTTPException('Página não encontrada', 404);
        }

        return (int) $page;
    }
}
