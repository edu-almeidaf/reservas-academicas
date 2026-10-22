<?php

namespace App\Controllers;

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

    private function page(Request $request): int
    {
        $page = $request->getParam('page', '1');

        if (!is_string($page) || !ctype_digit($page) || (int) $page < 1) {
            throw new HTTPException('Página não encontrada', 404);
        }

        return (int) $page;
    }
}
