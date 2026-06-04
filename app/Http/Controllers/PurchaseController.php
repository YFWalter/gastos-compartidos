<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Services\InstallmentGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    /**
     * Listado de compras del usuario autenticado.
     */
    public function index(Request $request): View
    {
        $purchases = $request->user()->purchases()
            ->withCount('installments')
            ->latest()
            ->paginate(15);

        return view('purchases.index', compact('purchases'));
    }

    /**
     * Formulario de alta. Requiere tener al menos un participante.
     */
    public function create(Request $request): View
    {
        $participants = $request->user()->participants()
            ->orderBy('nombre')->orderBy('apellido')->get();

        return view('purchases.create', compact('participants'));
    }

    /**
     * Crea la compra, su reparto y genera las cuotas con el monto de cada participante.
     */
    public function store(PurchaseRequest $request, InstallmentGenerator $generator): RedirectResponse
    {
        $data = $request->validated();

        $purchase = DB::transaction(function () use ($request, $data, $generator) {
            $purchase = $request->user()->purchases()->create([
                'descripcion'         => $data['descripcion'],
                'monto_total'         => $data['monto_total'],
                'cantidad_cuotas'     => $data['cantidad_cuotas'],
                'fecha_primera_cuota' => $data['fecha_primera_cuota'],
                'notas'               => $data['notas'] ?? null,
            ]);

            foreach ($data['splits'] as $split) {
                $purchase->splits()->create([
                    'participant_id' => $split['participant_id'],
                    'porcentaje'     => $split['porcentaje'],
                ]);
            }

            $purchase->load('splits');
            $generator->generate($purchase);

            return $purchase;
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Compra registrada con sus cuotas.');
    }

    /**
     * Detalle de una compra: reparto, cuotas y monto por participante.
     */
    public function show(Purchase $purchase): View
    {
        $this->authorizePurchase($purchase);

        $purchase->load([
            'splits.participant',
            'installments' => fn ($q) => $q->orderBy('numero'),
            'installments.shares.participant',
        ]);

        return view('purchases.show', compact('purchase'));
    }

    /**
     * Edición de datos descriptivos (no del monto/cuotas/reparto).
     */
    public function edit(Purchase $purchase): View
    {
        $this->authorizePurchase($purchase);

        return view('purchases.edit', compact('purchase'));
    }

    /**
     * Actualiza solo descripción y notas (no regenera cuotas).
     */
    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorizePurchase($purchase);

        $data = $request->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'notas'       => ['nullable', 'string', 'max:2000'],
        ]);

        $purchase->update($data);

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Compra actualizada.');
    }

    /**
     * Elimina la compra (las cuotas y repartos se borran en cascada).
     */
    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->authorizePurchase($purchase);

        $purchase->delete();

        return redirect()->route('purchases.index')
            ->with('status', 'Compra eliminada.');
    }

    /**
     * Asegura que la compra pertenece al usuario autenticado.
     */
    private function authorizePurchase(Purchase $purchase): void
    {
        abort_unless($purchase->user_id === auth()->id(), 403);
    }
}
