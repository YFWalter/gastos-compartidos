<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use App\Services\ServiceChargeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Listado de servicios del usuario autenticado.
     */
    public function index(Request $request): View
    {
        $services = $request->user()->services()
            ->withCount('charges')
            ->latest()
            ->paginate(15);

        return view('services.index', compact('services'));
    }

    /**
     * Formulario de alta. Requiere tener al menos un participante.
     */
    public function create(Request $request): View
    {
        $participants = $request->user()->participants()
            ->orderBy('nombre')->orderBy('apellido')->get();

        return view('services.create', compact('participants'));
    }

    /**
     * Crea el servicio, su reparto y el primer cargo con el monto de cada participante.
     */
    public function store(ServiceRequest $request, ServiceChargeGenerator $generator): RedirectResponse
    {
        $data = $request->validated();

        $service = DB::transaction(function () use ($request, $data, $generator) {
            $service = $request->user()->services()->create([
                'descripcion'              => $data['descripcion'],
                'monto_mensual'            => $data['monto_mensual'],
                'fecha_primer_vencimiento' => $data['fecha_primer_vencimiento'],
                'notas'                    => $data['notas'] ?? null,
                'avisar_participantes'     => $request->boolean('avisar_participantes'),
            ]);

            foreach ($data['splits'] as $split) {
                $service->splits()->create([
                    'participant_id' => $split['participant_id'],
                    'porcentaje'     => $split['porcentaje'],
                ]);
            }

            $service->load('splits');
            $generator->generarPrimerCargo($service);

            return $service;
        });

        return redirect()->route('services.show', $service)
            ->with('status', 'Servicio registrado con su primer cargo.');
    }

    /**
     * Detalle de un servicio: reparto, cargos generados y monto por participante.
     */
    public function show(Service $service): View
    {
        $this->authorizeService($service);

        $service->load([
            'splits.participant',
            'charges' => fn ($q) => $q->orderBy('numero'),
            'charges.shares.participant',
        ]);

        return view('services.show', compact('service'));
    }

    /**
     * Edición de datos descriptivos y del monto mensual (no del reparto/vencimiento).
     */
    public function edit(Service $service): View
    {
        $this->authorizeService($service);

        return view('services.edit', compact('service'));
    }

    /**
     * Actualiza descripción, notas, aviso a participantes y monto mensual.
     * El monto mensual solo afecta a los cargos que se generen de ahora en más:
     * los ya generados quedan con el monto que tenían al crearse (no se regeneran).
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorizeService($service);

        $data = $request->validate([
            'descripcion'   => ['required', 'string', 'max:255'],
            'monto_mensual' => ['required', 'numeric', 'min:0.01', 'max:99999999999'],
            'notas'         => ['nullable', 'string', 'max:2000'],
        ]);
        $data['avisar_participantes'] = $request->boolean('avisar_participantes');

        $service->update($data);

        return redirect()->route('services.show', $service)
            ->with('status', 'Servicio actualizado.');
    }

    /**
     * Cancela el servicio: detiene la generación de cargos futuros.
     * Los cargos pendientes ya generados no se tocan.
     */
    public function cancelar(Service $service): RedirectResponse
    {
        $this->authorizeService($service);

        $service->cancelar();

        return redirect()->route('services.show', $service)
            ->with('status', 'Servicio cancelado. No se van a generar más cargos.');
    }

    /**
     * Elimina el servicio (los cargos y repartos se borran en cascada).
     */
    public function destroy(Service $service): RedirectResponse
    {
        $this->authorizeService($service);

        $service->delete();

        return redirect()->route('services.index')
            ->with('status', 'Servicio eliminado.');
    }

    /**
     * Asegura que el servicio pertenece al usuario autenticado.
     */
    private function authorizeService(Service $service): void
    {
        abort_unless($service->user_id === auth()->id(), 403);
    }
}
