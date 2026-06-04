<?php

namespace App\Services;

use App\Models\Purchase;

class InstallmentGenerator
{
    /**
     * Genera las cuotas de una compra y, dentro de cada cuota,
     * el monto que le corresponde a cada participante según su porcentaje.
     *
     * La compra debe tener sus `splits` ya cargados.
     */
    public function generate(Purchase $purchase): void
    {
        $splits = $purchase->splits;
        $cuotas = (int) $purchase->cantidad_cuotas;
        $totalCents = (int) round(((float) $purchase->monto_total) * 100);

        // Reparto del total entre las N cuotas (los centavos sobrantes caen en las primeras).
        $montosCuota = $this->distribuir($totalCents, $cuotas);

        $porcentajes = $splits->map(fn ($s) => (float) $s->porcentaje)->all();

        foreach (range(1, $cuotas) as $numero) {
            $centsCuota = $montosCuota[$numero - 1];

            $installment = $purchase->installments()->create([
                'numero'      => $numero,
                'vencimiento' => $purchase->fecha_primera_cuota->copy()->addMonthsNoOverflow($numero - 1),
                'monto'       => $centsCuota / 100,
                'estado'      => 'pendiente',
            ]);

            // Reparto del monto de la cuota entre los participantes según su %.
            $centsParticipante = $this->distribuirPorPorcentaje($centsCuota, $porcentajes);

            foreach ($splits->values() as $idx => $split) {
                $installment->shares()->create([
                    'participant_id' => $split->participant_id,
                    'monto'          => $centsParticipante[$idx] / 100,
                    'estado'         => 'pendiente',
                ]);
            }
        }
    }

    /**
     * Reparte un total (en centavos) en N partes lo más iguales posible.
     * El sobrante se distribuye de a 1 centavo entre las primeras partes.
     *
     * @return array<int, int>
     */
    private function distribuir(int $totalCents, int $partes): array
    {
        $base = intdiv($totalCents, $partes);
        $sobrante = $totalCents - ($base * $partes);

        $resultado = array_fill(0, $partes, $base);
        for ($i = 0; $i < $sobrante; $i++) {
            $resultado[$i]++;
        }

        return $resultado;
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
