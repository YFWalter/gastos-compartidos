<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\ServiceChargeGenerator;
use Illuminate\Console\Command;

class GenerarCargosServicios extends Command
{
    protected $signature = 'servicios:generar-cargos
                            {--dias-anticipacion=5 : Días antes del vencimiento en que se genera el cargo del próximo mes}';

    protected $description = 'Genera el próximo cargo mensual de cada servicio activo cuando se acerca su vencimiento';

    public function handle(ServiceChargeGenerator $generator): int
    {
        $diasAnticipacion = max(0, (int) $this->option('dias-anticipacion'));

        $services = Service::where('estado', 'activo')->get();

        if ($services->isEmpty()) {
            $this->info('No hay servicios activos.');

            return self::SUCCESS;
        }

        $generados = 0;

        foreach ($services as $service) {
            $ultimoCargo = $service->charges()->orderByDesc('vencimiento')->first();

            if (! $ultimoCargo) {
                // No debería pasar (el primer cargo se genera al crear el servicio), pero por las dudas no rompe.
                $this->warn("Servicio «{$service->descripcion}» no tiene ningún cargo generado. Se omite.");
                continue;
            }

            $proximoVencimiento = $ultimoCargo->vencimiento->copy()->addMonthsNoOverflow();

            if (now()->startOfDay()->lt($proximoVencimiento->copy()->subDays($diasAnticipacion))) {
                continue; // todavía falta para el próximo cargo
            }

            $cargo = $generator->generarSiguienteCargo($service);
            $generados++;
            $this->line("  Cargo #{$cargo->numero} de «{$service->descripcion}» → vence {$cargo->vencimiento->format('d/m/Y')}");
        }

        $this->newLine();
        $this->info("Listo: {$generados} cargo(s) generado(s).");

        return self::SUCCESS;
    }
}
