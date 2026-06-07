<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\View\View;

class GuestParticipantController extends Controller
{
    /**
     * Vista pública (sin login) de las cuotas de un participante, identificado por token.
     * Solo lectura: muestra cuánto debe, qué pagó y los vencimientos.
     */
    public function show(Participant $participant): View
    {
        $shares = $participant->installmentShares()
            ->with(['installment.purchase'])
            ->get()
            ->sortBy(fn ($share) => optional($share->installment)->vencimiento)
            ->values();

        $totalPendiente = $shares->where('estado', 'pendiente')->sum('monto');
        $totalPagado = $shares->where('estado', 'pagado')->sum('monto');

        return view('guest.participant', compact('participant', 'shares', 'totalPendiente', 'totalPagado'));
    }
}
