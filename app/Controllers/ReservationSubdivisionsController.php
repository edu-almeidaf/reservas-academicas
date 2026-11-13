<?php

namespace App\Controllers;

use App\Models\Reservation;
use App\Models\ReservationSubdivision;
use Core\Exceptions\HTTPException;
use Core\Http\Controllers\Controller;
use Core\Http\Request;
use Lib\FlashMessage;

class ReservationSubdivisionsController extends Controller
{
    public function destroy(Request $request): void
    {
        $reservation = $this->findReservation($request);

        if (!$reservation->isPending()) {
            FlashMessage::danger('Somente reservas pendentes podem ser alteradas.');
            $this->redirectTo(route('reservations.show', ['id' => $reservation->id]));
            return;
        }

        $link = $this->findLink($reservation, $request);

        if ($link->destroy()) {
            FlashMessage::success('Subdivisão removida da reserva!');
        } else {
            FlashMessage::danger('Não foi possível remover a subdivisão da reserva.');
        }

        $this->redirectTo(route('reservations.show', ['id' => $reservation->id]));
    }

    private function findReservation(Request $request): Reservation
    {
        $id = $request->getParam('reservation_id');
        $reservation = is_string($id) && ctype_digit($id)
            ? $this->current_user->reservations()->findById((int) $id)
            : null;

        if (!$reservation instanceof Reservation) {
            throw new HTTPException('Reserva não encontrada', 404);
        }

        return $reservation;
    }

    private function findLink(Reservation $reservation, Request $request): ReservationSubdivision
    {
        $id = $request->getParam('id');
        $link = is_string($id) && ctype_digit($id)
            ? ReservationSubdivision::findBy(['reservation_id' => $reservation->id, 'subdivision_id' => (int) $id])
            : null;

        if ($link === null) {
            throw new HTTPException('Subdivisão não encontrada na reserva', 404);
        }

        return $link;
    }
}
