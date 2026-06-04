<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use App\Models\InstallmentShare;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        // Base: participaciones de cuotas que pertenecen a compras del usuario.
        $sharesQuery = InstallmentShare::whereHas(
            'installment.purchase',
            fn ($q) => $q->where('user_id', $userId)
        );

        $totalPendiente = (clone $sharesQuery)->where('estado', 'pendiente')->sum('monto');
        $totalPagado    = (clone $sharesQuery)->where('estado', 'pagado')->sum('monto');

        $stats = [
            'compras'      => $request->user()->purchases()->count(),
            'participantes' => $request->user()->participants()->count(),
            'pendiente'    => $totalPendiente,
            'pagado'       => $totalPagado,
        ];

        // Próximas cuotas con saldo pendiente.
        $proximas = Installment::query()
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $userId))
            ->where('estado', 'pendiente')
            ->with('purchase')
            ->orderBy('vencimiento')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'proximas'));
    }
}
