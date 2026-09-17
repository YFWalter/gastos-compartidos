<?php

namespace Tests\Feature;

use App\Mail\CuotasParticipanteMail;
use App\Mail\ResumenCuotasMail;
use App\Models\Participant;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ServiciosFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un usuario con dos participantes y un servicio ya generado (con su primer cargo).
     *
     * @return array{user: User, juan: Participant, ana: Participant, service: Service}
     */
    private function escenario(int $montoMensual = 10000, bool $avisarParticipantes = true): array
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/participants', [
            'nombre' => 'Juan', 'apellido' => 'Pérez', 'email' => 'juan@example.com',
        ]);
        $this->actingAs($user)->post('/participants', [
            'nombre' => 'Ana', 'apellido' => 'Gómez', 'email' => 'ana@example.com',
        ]);

        $juan = Participant::where('nombre', 'Juan')->first();
        $ana = Participant::where('nombre', 'Ana')->first();

        $this->actingAs($user)->post('/services', [
            'descripcion'              => 'Netflix',
            'monto_mensual'            => $montoMensual,
            'fecha_primer_vencimiento' => '2026-08-10',
            'avisar_participantes'     => $avisarParticipantes ? 1 : 0,
            'splits'                   => [
                ['participant_id' => $juan->id, 'porcentaje' => 60],
                ['participant_id' => $ana->id, 'porcentaje' => 40],
            ],
        ]);

        $service = Service::where('descripcion', 'Netflix')->first();

        return compact('user', 'juan', 'ana', 'service');
    }

    public function test_crear_servicio_genera_el_primer_cargo_con_reparto_exacto(): void
    {
        ['service' => $service, 'juan' => $juan, 'ana' => $ana] = $this->escenario(10000);

        $this->assertSame(1, $service->charges()->count());

        $cargo = $service->charges()->first();
        $this->assertEqualsWithDelta(10000, $cargo->monto, 0.001);
        $this->assertSame('2026-08-10', $cargo->vencimiento->format('Y-m-d'));

        $shareJuan = $cargo->shares()->where('participant_id', $juan->id)->first();
        $shareAna = $cargo->shares()->where('participant_id', $ana->id)->first();
        $this->assertEqualsWithDelta(6000, $shareJuan->monto, 0.001);
        $this->assertEqualsWithDelta(4000, $shareAna->monto, 0.001);
    }

    public function test_cambiar_el_monto_mensual_no_afecta_cargos_ya_generados_solo_los_futuros(): void
    {
        ['user' => $user, 'service' => $service, 'juan' => $juan, 'ana' => $ana] = $this->escenario(10000);

        // Aumento de tarifa: de 10000 a 15000.
        $this->actingAs($user)->patch(route('services.update', $service), [
            'descripcion'          => $service->descripcion,
            'monto_mensual'        => 15000,
            'avisar_participantes' => 1,
        ])->assertRedirect();

        $this->assertEqualsWithDelta(15000, $service->fresh()->monto_mensual, 0.001);

        // El cargo #1, ya generado con la tarifa vieja, no cambia.
        $cargoViejo = $service->charges()->first();
        $this->assertEqualsWithDelta(10000, $cargoViejo->fresh()->monto, 0.001);

        // El próximo cargo que se genere usa la tarifa nueva, repartida igual por %.
        $this->travelTo('2026-09-05');
        $this->artisan('servicios:generar-cargos')->assertSuccessful();
        $this->travelBack();

        $cargoNuevo = $service->charges()->orderByDesc('vencimiento')->first();
        $this->assertEqualsWithDelta(15000, $cargoNuevo->monto, 0.001);

        $shareJuan = $cargoNuevo->shares()->where('participant_id', $juan->id)->first();
        $shareAna = $cargoNuevo->shares()->where('participant_id', $ana->id)->first();
        $this->assertEqualsWithDelta(9000, $shareJuan->monto, 0.001);
        $this->assertEqualsWithDelta(6000, $shareAna->monto, 0.001);
    }

    public function test_listado_de_participantes_muestra_el_pendiente_combinado_de_compras_y_servicios(): void
    {
        ['user' => $user, 'juan' => $juan] = $this->escenario(10000);
        // Netflix: Juan 60% de 10000 = 6000 pendiente.

        $this->actingAs($user)->post('/purchases', [
            'descripcion'          => 'Notebook',
            'monto_total'          => 5000,
            'cantidad_cuotas'      => 1,
            'fecha_primera_cuota'  => '2026-08-10',
            'avisar_participantes' => 1,
            'splits'               => [
                ['participant_id' => $juan->id, 'porcentaje' => 100],
            ],
        ]);
        // Juan ahora debe 6000 (servicio) + 5000 (compra) = 11000.

        $this->actingAs($user)->get(route('participants.index'))
            ->assertStatus(200)
            ->assertSee('11.000,00') // Juan: compra + servicio combinados
            ->assertSee('4.000,00'); // Ana: solo el servicio (40% de 10000)
    }

    public function test_porcentajes_que_no_suman_100_son_rechazados(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/participants', ['nombre' => 'Juan']);
        $juan = Participant::where('nombre', 'Juan')->first();

        $this->actingAs($user)->post('/services', [
            'descripcion'              => 'Internet',
            'monto_mensual'            => 5000,
            'fecha_primer_vencimiento' => '2026-08-10',
            'splits'                   => [
                ['participant_id' => $juan->id, 'porcentaje' => 50],
            ],
        ])->assertSessionHasErrors('splits');

        $this->assertDatabaseMissing('services', ['descripcion' => 'Internet']);
    }

    public function test_generar_cargos_respeta_la_ventana_de_anticipacion_y_es_idempotente(): void
    {
        ['service' => $service] = $this->escenario();

        // El próximo cargo vence 2026-09-10. Con 5 días de anticipación y "hoy" muy anterior, no se genera.
        $this->travelTo('2026-08-20');
        $this->artisan('servicios:generar-cargos')->assertSuccessful();
        $this->assertSame(1, $service->charges()->count());

        // A partir del 2026-09-05 (5 días antes del vencimiento) sí corresponde generarlo.
        $this->travelTo('2026-09-05');
        $this->artisan('servicios:generar-cargos')->assertSuccessful();
        $this->assertSame(2, $service->fresh()->charges()->count());

        $nuevoCargo = $service->charges()->orderByDesc('vencimiento')->first();
        $this->assertSame(2, $nuevoCargo->numero);
        $this->assertSame('2026-09-10', $nuevoCargo->vencimiento->format('Y-m-d'));

        // Correr el comando de nuevo el mismo día no duplica el cargo.
        $this->artisan('servicios:generar-cargos')->assertSuccessful();
        $this->assertSame(2, $service->fresh()->charges()->count());

        $this->travelBack(); // limpio el viaje en el tiempo para no afectar otros tests
    }

    public function test_cancelar_servicio_detiene_generacion_futura_pero_no_toca_cargos_pendientes(): void
    {
        ['user' => $user, 'service' => $service] = $this->escenario();

        $this->actingAs($user)->patch(route('services.cancelar', $service))->assertRedirect();
        $this->assertSame('cancelado', $service->fresh()->estado);
        $this->assertSame(1, $service->fresh()->charges()->count());
        $this->assertSame('pendiente', $service->charges()->first()->estado);

        $this->travelTo('2026-09-20');
        $this->artisan('servicios:generar-cargos')->assertSuccessful();
        $this->assertSame(1, $service->fresh()->charges()->count());

        $this->travel(-100)->days();
    }

    public function test_marcar_cargo_y_participacion_actualiza_estado(): void
    {
        ['user' => $user, 'service' => $service] = $this->escenario();
        $cargo = $service->charges()->first();
        $shares = $cargo->shares;

        $this->actingAs($user)->patch(route('service-charge-shares.toggle', $shares[0]));
        $this->assertSame('pagado', $shares[0]->fresh()->estado);
        $this->assertSame('pendiente', $cargo->fresh()->estado);

        $this->actingAs($user)->patch(route('service-charge-shares.toggle', $shares[1]));
        $this->assertSame('pagado', $cargo->fresh()->estado);

        $this->actingAs($user)->patch(route('service-charges.toggle', $cargo));
        $this->assertSame('pendiente', $cargo->fresh()->estado);
        $this->assertSame(0, $cargo->shares()->where('estado', 'pagado')->count());
    }

    public function test_recordatorios_incluyen_cargos_de_servicios_pendientes(): void
    {
        Mail::fake();
        $this->escenario();

        $this->artisan('recordatorios:enviar', ['--todas' => true, '--pausa' => 0])
            ->assertSuccessful();

        Mail::assertSent(ResumenCuotasMail::class);
        Mail::assertSent(CuotasParticipanteMail::class, 2);
    }

    public function test_avisar_participantes_desactivado_no_envia_email_a_participantes_del_servicio(): void
    {
        Mail::fake();
        $this->escenario(avisarParticipantes: false);

        $this->artisan('recordatorios:enviar', ['--todas' => true, '--pausa' => 0])
            ->assertSuccessful();

        Mail::assertSent(ResumenCuotasMail::class);
        Mail::assertNotSent(CuotasParticipanteMail::class);
    }

    public function test_paginas_de_servicios_responden_ok(): void
    {
        ['user' => $user, 'service' => $service] = $this->escenario();

        $this->actingAs($user)->get(route('services.index'))->assertStatus(200);
        $this->actingAs($user)->get(route('services.create'))->assertStatus(200);
        $this->actingAs($user)->get(route('services.show', $service))->assertStatus(200);
        $this->actingAs($user)->get(route('services.edit', $service))->assertStatus(200);
        $this->actingAs($user)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($user)->get(route('tracking.index'))->assertStatus(200);
    }

    public function test_vista_de_invitado_muestra_los_cargos_de_servicio(): void
    {
        ['juan' => $juan] = $this->escenario();

        $this->get(route('guest.participant', $juan->token))
            ->assertStatus(200)
            ->assertSee('Netflix')
            ->assertSee('Cargo mensual');
    }
}
