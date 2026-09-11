<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceCharge;

class ServiceChargeGenerator
{
    /**
     * Genera el primer cargo de un servicio recién creado, con el reparto
     * entre participantes según su porcentaje. El servicio debe tener
     * sus `splits` ya cargados.
     */
    public function generarPrimerCargo(Service $service): ServiceCharge
    {
        return $this->crearCargo($service, numero: 1, vencimiento: $service->fecha_primer_vencimiento->copy());
    }

    /**
     * Genera el cargo siguiente al último ya existente del servicio,
     * un mes después (mismo día, con `addMonthsNoOverflow` para meses cortos).
     */
    public function generarSiguienteCargo(Service $service): ServiceCharge
    {
        $ultimoCargo = $service->charges()->orderByDesc('vencimiento')->firstOrFail();

        return $this->crearCargo(
            $service,
            numero: $ultimoCargo->numero + 1,
            vencimiento: $ultimoCargo->vencimiento->copy()->addMonthsNoOverflow()
        );
    }

    private function crearCargo(Service $service, int $numero, \Illuminate\Support\Carbon $vencimiento): ServiceCharge
    {
        $totalCents = (int) round(((float) $service->monto_mensual) * 100);

        $charge = $service->charges()->create([
            'numero'      => $numero,
            'vencimiento' => $vencimiento,
            'monto'       => $totalCents / 100,
            'estado'      => 'pendiente',
        ]);

        $splits = $service->splits;
        $porcentajes = $splits->map(fn ($s) => (float) $s->porcentaje)->all();
        $centsParticipante = $this->distribuirPorPorcentaje($totalCents, $porcentajes);

        foreach ($splits->values() as $idx => $split) {
            $charge->shares()->create([
                'participant_id' => $split->participant_id,
                'monto'          => $centsParticipante[$idx] / 100,
                'estado'         => 'pendiente',
            ]);
        }

        return $charge;
    }

    /**
     * Reparte un total (en centavos) según una lista de porcentajes que suman 100.
     * La última parte absorbe la diferencia de redondeo para que la suma sea exacta.
     *
     * @param  array<int, float>  $porcentajes
     * @return array<int, int>
     */
    private function distribuirPorPorcentaje(int $totalCents, array $porcentajes): array
    {
        $resultado = [];
        $asignado = 0;
        $n = count($porcentajes);

        foreach ($porcentajes as $i => $porcentaje) {
            if ($i < $n - 1) {
                $cents = (int) round($totalCents * $porcentaje / 100);
                $asignado += $cents;
            } else {
                $cents = $totalCents - $asignado;
            }
            $resultado[$i] = $cents;
        }

        return $resultado;
    }
}
