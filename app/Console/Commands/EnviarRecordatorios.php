<?php

namespace App\Console\Commands;

use App\Mail\CuotasParticipanteMail;
use App\Mail\ResumenCuotasMail;
use App\Models\Installment;
use App\Models\ServiceCharge;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatorios extends Command
{
    protected $signature = 'recordatorios:enviar
                            {--dias=3 : Avisar de cuotas/cargos que vencen dentro de los próximos N días}
                            {--todas : Ignorar fechas y avisar de TODAS las cuotas/cargos pendientes (útil para probar)}
                            {--pausa=1 : Segundos de espera entre cada email (evita límites de envío por segundo)}';

    protected $description = 'Envía recordatorios de cuotas y cargos por pagar al dueño de cada compra/servicio y a sus participantes';

    /** @var array<int, array{user: \App\Models\User, cuotas: array, total: float}> */
    private array $owners = [];

    /** @var array<int, array{participant: \App\Models\Participant, items: array, total: float}> */
    private array $participants = [];

    public function handle(): int
    {
        $dias = (int) $this->option('dias');
        $todas = (bool) $this->option('todas');

        $desde = now()->startOfDay();
        $hasta = now()->copy()->addDays($dias)->endOfDay();

        if (! $todas) {
            $this->info("Buscando cuotas y cargos que vencen entre {$desde->format('d/m/Y')} y {$hasta->format('d/m/Y')}...");
        } else {
            $this->info('Buscando TODAS las cuotas y cargos pendientes (--todas)...');
        }

        $installments = Installment::query()
            ->where('estado', 'pendiente')
            ->with(['purchase.user', 'shares' => fn ($q) => $q->where('estado', 'pendiente'), 'shares.participant'])
            ->when(! $todas, fn ($q) => $q->whereBetween('vencimiento', [$desde, $hasta]))
            ->orderBy('vencimiento')
            ->get();

        $charges = ServiceCharge::query()
            ->where('estado', 'pendiente')
            ->with(['service.user', 'shares' => fn ($q) => $q->where('estado', 'pendiente'), 'shares.participant'])
            ->when(! $todas, fn ($q) => $q->whereBetween('vencimiento', [$desde, $hasta]))
            ->orderBy('vencimiento')
            ->get();

        if ($installments->isEmpty() && $charges->isEmpty()) {
            $this->warn('No hay cuotas ni cargos pendientes que avisar.');

            return self::SUCCESS;
        }

        foreach ($installments as $installment) {
            $this->agregar(
                ownerId: $installment->purchase->user_id,
                ownerUser: $installment->purchase->user,
                avisarParticipantes: $installment->purchase->avisar_participantes,
                descripcion: $installment->purchase->descripcion,
                detalle: "cuota {$installment->numero}/{$installment->purchase->cantidad_cuotas}",
                vencimiento: $installment->vencimiento,
                sharesPendientes: $installment->shares,
            );
        }

        foreach ($charges as $charge) {
            $this->agregar(
                ownerId: $charge->service->user_id,
                ownerUser: $charge->service->user,
                avisarParticipantes: $charge->service->avisar_participantes,
                descripcion: $charge->service->descripcion,
                detalle: 'cargo mensual',
                vencimiento: $charge->vencimiento,
                sharesPendientes: $charge->shares,
            );
        }

        // Armo la lista de envíos (dueños primero, luego participantes).
        $envios = [];
        foreach ($this->owners as $data) {
            if (! $data['user']?->email) {
                continue;
            }
            $envios[] = [
                'to'       => $data['user']->email,
                'mailable' => new ResumenCuotasMail($data['user']->name, $data['cuotas'], (float) $data['total'], route('tracking.index')),
                'label'    => "Resumen → {$data['user']->email} (" . count($data['cuotas']) . ' ítems)',
            ];
        }
        foreach ($this->participants as $data) {
            $envios[] = [
                'to'       => $data['participant']->email,
                'mailable' => new CuotasParticipanteMail(
                    $data['participant']->nombre_completo,
                    $data['items'],
                    (float) $data['total'],
                    $data['participant']->enlace_invitado,
                ),
                'label'    => "Aviso → {$data['participant']->email} (" . count($data['items']) . ' ítems)',
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
            ->concat($charges->flatMap->shares)
            ->filter(fn ($s) => $s->participant && ! $s->participant->email)
            ->pluck('participant.nombre_completo')->unique();

        if ($sinEmail->isNotEmpty()) {
            $this->warn('Participantes sin email (no recibieron aviso): ' . $sinEmail->implode(', '));
        }

        return $fallidos > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Agrega una cuota o cargo pendiente al resumen del dueño y, si corresponde,
     * al aviso de cada participante con saldo pendiente en ella.
     */
    private function agregar(
        int $ownerId,
        ?\App\Models\User $ownerUser,
        bool $avisarParticipantes,
        string $descripcion,
        string $detalle,
        \Illuminate\Support\Carbon $vencimiento,
        Collection $sharesPendientes,
    ): void {
        // --- Datos para el dueño (resumen) ---
        $detalleParticipantes = $sharesPendientes->map(fn ($s) => [
            'nombre' => $s->participant?->nombre_completo ?? '—',
            'monto'  => $s->monto,
        ])->all();
        $pendiente = (float) $sharesPendientes->sum(fn ($s) => (float) $s->monto);

        $this->owners[$ownerId]['user'] = $ownerUser;
        $this->owners[$ownerId]['cuotas'][] = [
            'descripcion' => $descripcion,
            'detalle'     => $detalle,
            'vencimiento' => $vencimiento,
            'pendiente'   => $pendiente,
            'detalle_participantes' => $detalleParticipantes,
        ];
        $this->owners[$ownerId]['total'] = ($this->owners[$ownerId]['total'] ?? 0) + $pendiente;

        // --- Datos para cada participante (su parte) ---
        if (! $avisarParticipantes) {
            return; // el dueño desactivó el aviso a participantes para esta compra/servicio
        }

        foreach ($sharesPendientes as $share) {
            $participant = $share->participant;
            if (! $participant || ! $participant->email) {
                continue; // sin email no se le puede avisar
            }
            $pid = $participant->id;
            $this->participants[$pid]['participant'] = $participant;
            $this->participants[$pid]['items'][] = [
                'descripcion' => $descripcion,
                'detalle'     => $detalle,
                'vencimiento' => $vencimiento,
                'monto'       => $share->monto,
            ];
            $this->participants[$pid]['total'] = ($this->participants[$pid]['total'] ?? 0) + (float) $share->monto;
        }
    }
}
