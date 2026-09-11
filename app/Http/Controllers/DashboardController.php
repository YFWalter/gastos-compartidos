<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use App\Models\InstallmentShare;
use App\Models\ServiceCharge;
use App\Models\ServiceChargeShare;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        // Base: participaciones de cuotas y de cargos de servicio del usuario.
        $installmentSharesQuery = InstallmentShare::whereHas(
            'installment.purchase',
            fn ($q) => $q->where('user_id', $userId)
        );
        $chargeSharesQuery = ServiceChargeShare::whereHas(
            'serviceCharge.service',
            fn ($q) => $q->where('user_id', $userId)
        );

        $totalPendiente = (clone $installmentSharesQuery)->where('estado', 'pendiente')->sum('monto')
            + (clone $chargeSharesQuery)->where('estado', 'pendiente')->sum('monto');
        $totalPagado = (clone $installmentSharesQuery)->where('estado', 'pagado')->sum('monto')
            + (clone $chargeSharesQuery)->where('estado', 'pagado')->sum('monto');

        $stats = [
            'compras'         => $request->user()->purchases()->count(),
            'servicios_activos' => $request->user()->services()->where('estado', 'activo')->count(),
            'participantes'   => $request->user()->participants()->count(),
            'pendiente'       => $totalPendiente,
            'pagado'          => $totalPagado,
        ];

        // Próximas cuotas y cargos con saldo pendiente, combinados y ordenados por vencimiento.
        $proximasCuotas = Installment::query()
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $userId))
            ->where('estado', 'pendiente')
            ->with('purchase')
            ->orderBy('vencimiento')
            ->limit(5)
            ->get()
            ->map(fn (Installment $i) => [
                'vencimiento' => $i->vencimiento,
                'descripcion' => $i->purchase->descripcion,
                'detalle'     => "{$i->numero} / {$i->purchase->cantidad_cuotas}",
                'monto'       => $i->monto,
                'url'         => route('purchases.show', $i->purchase),
            ]);

        $proximosCargos = ServiceCharge::query()
            ->whereHas('service', fn ($q) => $q->where('user_id', $userId))
            ->where('estado', 'pendiente')
            ->with('service')
            ->orderBy('vencimiento')
            ->limit(5)
            ->get()
            ->map(fn (ServiceCharge $c) => [
                'vencimiento' => $c->vencimiento,
                'descripcion' => $c->service->descripcion,
                'detalle'     => 'Cargo mensual',
                'monto'       => $c->monto,
                'url'         => route('services.show', $c->service),
            ]);

        $proximas = $proximasCuotas->concat($proximosCargos)
            ->sortBy('vencimiento')
            ->take(5)
            ->values();

        return view('dashboard', compact('stats', 'proximas'));
    }
}
