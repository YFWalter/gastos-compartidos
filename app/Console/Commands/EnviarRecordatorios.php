<?php

namespace App\Console\Commands;

use App\Mail\CuotasParticipanteMail;
use App\Mail\ResumenCuotasMail;
use App\Models\Installment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatorios extends Command
{
    protected $signature = 'recordatorios:enviar
                            {--dias=3 : Avisar de cuotas que vencen dentro de los próximos N días}
                            {--todas : Ignorar fechas y avisar de TODAS las cuotas pendientes (útil para probar)}
                            {--pausa=1 : Segundos de espera entre cada email (evita límites de envío por segundo)}';

    protected $description = 'Envía recordatorios de cuotas por pagar al dueño de cada compra y a sus participantes';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $todas = (bool) $this->option('todas');

        $query = Installment::query()
            ->where('estado', 'pendiente')
            ->with([
                'purchase.user',
                'shares' => fn ($q) => $q->where('estado', 'pendiente'),
                'shares.participant',
            ]);

        if (! $todas) {
            $desde = now()->startOfDay();
            $hasta = now()->copy()->addDays($dias)->endOfDay();
            $query->whereBetween('vencimiento', [$desde, $hasta]);
            $this->info("Buscando cuotas que vencen entre {$desde->format('d/m/Y')} y {$hasta->format('d/m/Y')}...");
        } else {
            $this->info('Buscando TODAS las cuotas pendientes (--todas)...');
        }

        $installments = $query->orderBy('vencimiento')->get();

        if ($installments->isEmpty()) {
            $this->warn('No hay cuotas pendientes que avisar.');

            return self::SUCCESS;
        }

        // Armo los datos agrupados para cada destinatario.
        $owners = [];        // userId  => ['user' => User, 'cuotas' => [...], 'total' => float]
        $participants = [];  // partId  => ['participant' => Participant, 'items' => [...], 'total' => float]

        foreach ($installments as $installment) {
            $purchase = $installment->purchase;
            $sharesPendientes = $installment->shares; // ya filtradas a 'pendiente'

            // --- Datos para el dueño (resumen) ---
            $uid = $purchase->user_id;
            $detalle = $sharesPendientes->map(fn ($s) => [
                'nombre' => $s->participant?->nombre_completo ?? '—',
                'monto'  => $s->monto,
            ])->all();
            $pendienteCuota = (float) $sharesPendientes->sum(fn ($s) => (float) $s->monto);

            $owners[$uid]['user'] = $purchase->user;
            $owners[$uid]['cuotas'][] = [
                'descripcion' => $purchase->descripcion,
                'numero'      => $installment->numero,
                'cantidad'    => $purchase->cantidad_cuotas,
                'vencimiento' => $installment->vencimiento,
                'pendiente'   => $pendienteCuota,
                'detalle'     => $detalle,
            ];
            $owners[$uid]['total'] = ($owners[$uid]['total'] ?? 0) + $pendienteCuota;

            // --- Datos para cada participante (su parte) ---
            foreach ($sharesPendientes as $share) {
                $participant = $share->participant;
                if (! $participant || ! $participant->email) {
                    continue; // sin email no se le puede avisar
                }
                $pid = $participant->id;
                $participants[$pid]['participant'] = $participant;
                $participants[$pid]['items'][] = [
                    'descripcion' => $purchase->descripcion,
                    'numero'      => $installment->numero,
                    'cantidad'    => $purchase->cantidad_cuotas,
                    'vencimiento' => $installment->vencimiento,
                    'monto'       => $share->monto,
                ];
                $participants[$pid]['total'] = ($participants[$pid]['total'] ?? 0) + (float) $share->monto;
            }
        }

        // Armo la lista de envíos (dueños primero, luego participantes).
        $envios = [];
        foreach ($owners as $data) {
            if (! $data['user']?->email) {
                continue;
            }
            $envios[] = [
                'to'       => $data['user']->email,
                'mailable' => new ResumenCuotasMail($data['user']->name, $data['cuotas'], (float) $data['total']),
                'label'    => "Resumen → {$data['user']->email} (" . count($data['cuotas']) . ' cuotas)',
            ];
        }
        foreach ($participants as $data) {
            $envios[] = [
                'to'       => $data['participant']->email,
                'mailable' => new CuotasParticipanteMail(
                    $data['participant']->nombre_completo,
                    $data['items'],
                    (float) $data['total'],
                    $data['participant']->enlace_invitado,
                ),
                'label'    => "Aviso → {$data['participant']->email} (" . count($data['items']) . ' cuotas)',
            ];
        }

        // Envío con pausa entre emails y tolerancia a fallos individuales.
        $pausa = max(0, (int) $this->option('pausa'));
        $enviados = 0;
        $fallidos = 0;

        foreach (array_values($envios) as $i => $envio) {
            if ($i > 0 && $pausa > 0) {
                sleep($pausa);
            }
            try {
                Mail::to($envio['to'])->send($envio['mailable']);
                $enviados++;
                $this->line("  {$envio['label']}");
            } catch (\Throwable $e) {
                $fallidos++;
                $this->warn("  FALLÓ {$envio['label']}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Listo: {$enviados} email(s) enviado(s)" . ($fallidos > 0 ? ", {$fallidos} fallido(s)." : '.'));

        $sinEmail = $installments->flatMap->shares
            ->filter(fn ($s) => $s->participant && ! $s->participant->email)
            ->pluck('participant.nombre_completo')->unique();

        if ($sinEmail->isNotEmpty()) {
            $this->warn('Participantes sin email (no recibieron aviso): ' . $sinEmail->implode(', '));
        }

        return $fallidos > 0 ? self::FAILURE : self::SUCCESS;
    }
}
