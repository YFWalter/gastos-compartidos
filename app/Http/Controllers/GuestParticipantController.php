<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\View\View;

class GuestParticipantController extends Controller
{
    /**
     * Vista pública (sin login) de las cuotas y cargos de un participante, identificado por token.
     * Solo lectura: muestra cuánto debe, qué pagó y los vencimientos.
     */
    public function show(Participant $participant): View
    {
        $itemsCuotas = $participant->installmentShares()
            ->with(['installment.purchase'])
            ->get()
            ->map(fn ($share) => [
                'descripcion' => $share->installment->purchase->descripcion,
                'detalle'     => "{$share->installment->numero} / {$share->installment->purchase->cantidad_cuotas}",
                'vencimiento' => $share->installment->vencimiento,
                'monto'       => $share->monto,
                'estado'      => $share->estado,
                'fecha_pago'  => $share->fecha_pago,
            ]);

        $itemsCargos = $participant->serviceChargeShares()
            ->with(['serviceCharge.service'])
            ->get()
            ->map(fn ($share) => [
                'descripcion' => $share->serviceCharge->service->descripcion,
                'detalle'     => 'Cargo mensual',
                'vencimiento' => $share->serviceCharge->vencimiento,
                'monto'       => $share->monto,
                'estado'      => $share->estado,
                'fecha_pago'  => $share->fecha_pago,
            ]);

        $items = $itemsCuotas->concat($itemsCargos)->sortBy('vencimiento')->values();

        $totalPendiente = $items->where('estado', 'pendiente')->sum('monto');
        $totalPagado = $items->where('estado', 'pagado')->sum('monto');

        return view('guest.participant', compact('participant', 'items', 'totalPendiente', 'totalPagado'));
    }
}
