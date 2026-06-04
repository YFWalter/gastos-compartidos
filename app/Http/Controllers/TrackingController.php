<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    /**
     * Seguimiento de cuotas del usuario, ordenadas por vencimiento y agrupadas por mes.
     */
    public function index(Request $request): View
    {
        $installments = Installment::query()
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['purchase', 'shares.participant'])
            ->orderBy('vencimiento')
            ->get();

        // Agrupadas por "YYYY-MM" para mostrarlas por mes en la vista.
        $porMes = $installments->groupBy(fn ($i) => $i->vencimiento->format('Y-m'));

        return view('tracking.index', compact('porMes'));
    }
}
