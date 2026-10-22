<?php

namespace App\Controllers;

use App\Models\Reservation;
use App\Models\ReservationSchedule;
use App\Models\Resource;
use Core\Exceptions\HTTPException;
use Core\Http\Controllers\Controller;
use Core\Http\Request;

class ReservationsController extends Controller
{
    private const PER_PAGE = 10;

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
