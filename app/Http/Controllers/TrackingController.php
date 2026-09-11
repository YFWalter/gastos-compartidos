<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use App\Models\ServiceCharge;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    /**
     * Seguimiento de cuotas y cargos del usuario, ordenados por vencimiento y agrupados por mes.
     */
    public function index(Request $request): View
    {
        $installments = Installment::query()
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['purchase', 'shares.participant'])
            ->orderBy('vencimiento')
            ->get();

        $charges = ServiceCharge::query()
            ->whereHas('service', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['service', 'shares.participant'])
            ->orderBy('vencimiento')
            ->get();

        // Agrupadas por "YYYY-MM" para mostrarlas por mes en la vista.
        $porMes = $installments->groupBy(fn ($i) => $i->vencimiento->format('Y-m'));
        $cargosPorMes = $charges->groupBy(fn ($c) => $c->vencimiento->format('Y-m'));

        return view('tracking.index', compact('porMes', 'cargosPorMes'));
    }
}
