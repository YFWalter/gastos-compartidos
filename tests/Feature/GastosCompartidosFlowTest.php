<?php

namespace Tests\Feature;

use App\Mail\CuotasParticipanteMail;
use App\Mail\ResumenCuotasMail;
use App\Models\Installment;
use App\Models\InstallmentShare;
use App\Models\Participant;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GastosCompartidosFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un usuario con dos participantes y una compra ya generada.
     *
     * @return array{user: User, juan: Participant, ana: Participant, purchase: Purchase}
     */
    private function escenario(int $monto = 10000, int $cuotas = 3): array
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

        $this->actingAs($user)->post('/purchases', [
            'descripcion'         => 'Notebook',
            'monto_total'         => $monto,
            'cantidad_cuotas'     => $cuotas,
            'fecha_primera_cuota' => '2026-08-10',
            'splits'              => [
                ['participant_id' => $juan->id, 'porcentaje' => 60],
                ['participant_id' => $ana->id, 'porcentaje' => 40],
            ],
        ]);

        $purchase = Purchase::where('descripcion', 'Notebook')->first();

        return compact('user', 'juan', 'ana', 'purchase');
    }

    public function test_crear_participante_genera_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/participants', ['nombre' => 'Juan'])
            ->assertRedirect(route('participants.index'));

        $participant = Participant::where('nombre', 'Juan')->first();
        $this->assertSame($user->id, $participant->user_id);
        $this->assertNotEmpty($participant->token);
    }

    public function test_al_crear_un_usuario_se_crea_su_participante_titular(): void
    {
        $user = User::factory()->create(['name' => 'Walter Yáñez']);

        $titular = $user->participants()->where('es_titular', true)->first();
        $this->assertNotNull($titular);
        $this->assertSame('Walter', $titular->nombre);
        $this->assertSame('Yáñez', $titular->apellido);
        $this->assertSame($user->email, $titular->email);
    }

    public function test_no_se_puede_eliminar_el_participante_titular(): void
    {
        $user = User::factory()->create();
        $titular = $user->participants()->where('es_titular', true)->first();

        $this->actingAs($user)->delete(route('participants.destroy', $titular))
            ->assertRedirect(route('participants.index'));

        $this->assertDatabaseHas('participants', ['id' => $titular->id]);
    }

    public function test_crear_compra_genera_cuotas_y_reparto_exacto(): void
    {
        ['purchase' => $purchase, 'juan' => $juan, 'ana' => $ana] = $this->escenario(10000, 3);

        // 3 cuotas + 6 participaciones (2 por cuota).
        $this->assertSame(3, $purchase->installments()->count());
        $this->assertSame(6, InstallmentShare::whereIn('installment_id', $purchase->installments()->pluck('id'))->count());

        // La suma de las cuotas es exactamente el total.
        $this->assertEqualsWithDelta(10000, $purchase->installments()->sum('monto'), 0.001);

        // Cada participante recibe exactamente su porcentaje del total.
        $totalJuan = InstallmentShare::where('participant_id', $juan->id)->sum('monto');
        $totalAna = InstallmentShare::where('participant_id', $ana->id)->sum('monto');
        $this->assertEqualsWithDelta(6000, $totalJuan, 0.001);
        $this->assertEqualsWithDelta(4000, $totalAna, 0.001);
    }

    public function test_porcentajes_que_no_suman_100_son_rechazados(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/participants', ['nombre' => 'Juan']);
        $juan = Participant::where('nombre', 'Juan')->first();

        $this->actingAs($user)->post('/purchases', [
            'descripcion' => 'X', 'monto_total' => 1000, 'cantidad_cuotas' => 2,
            'fecha_primera_cuota' => '2026-08-10',
            'splits' => [['participant_id' => $juan->id, 'porcentaje' => 80]],
        ])->assertSessionHasErrors('splits');

        $this->assertSame(0, Purchase::count());
    }

    public function test_marcar_participaciones_actualiza_estado_de_la_cuota(): void
    {
        ['purchase' => $purchase] = $this->escenario();
        $user = $purchase->user;
        $cuota = $purchase->installments()->orderBy('numero')->first();
        $shares = $cuota->shares()->orderBy('id')->get();

        // Pago una sola participación: la cuota sigue pendiente.
        $this->actingAs($user)->patch(route('shares.toggle', $shares[0]));
        $this->assertSame('pagado', $shares[0]->fresh()->estado);
        $this->assertSame('pendiente', $cuota->fresh()->estado);

        // Pago la otra: la cuota pasa a pagada automáticamente.
        $this->actingAs($user)->patch(route('shares.toggle', $shares[1]));
        $this->assertSame('pagada', $cuota->fresh()->estado);
    }

    public function test_marcar_cuota_completa_y_revertir(): void
    {
        ['purchase' => $purchase] = $this->escenario();
        $user = $purchase->user;
        $cuota = $purchase->installments()->first();

        $this->actingAs($user)->patch(route('installments.toggle', $cuota));
        $this->assertSame('pagada', $cuota->fresh()->estado);
        $this->assertSame(0, $cuota->shares()->where('estado', 'pendiente')->count());

        $this->actingAs($user)->patch(route('installments.toggle', $cuota));
        $this->assertSame('pendiente', $cuota->fresh()->estado);
        $this->assertSame(0, $cuota->shares()->where('estado', 'pagado')->count());
    }

    public function test_link_de_invitado_es_publico_y_muestra_las_cuotas(): void
    {
        ['juan' => $juan] = $this->escenario();

        // Sin autenticación.
        $this->get(route('guest.participant', $juan->token))
            ->assertStatus(200)
            ->assertSee('Juan Pérez')
            ->assertSee('Notebook');

        // Token inválido => 404.
        $this->get('/p/token-inexistente')->assertNotFound();
    }

    public function test_regenerar_token_invalida_el_anterior(): void
    {
        ['user' => $user, 'juan' => $juan] = $this->escenario();
        $tokenViejo = $juan->token;

        $this->actingAs($user)->patch(route('participants.token', $juan))->assertRedirect();

        $this->assertNotSame($tokenViejo, $juan->fresh()->token);
        $this->get(route('guest.participant', $tokenViejo))->assertNotFound();
        $this->get(route('guest.participant', $juan->fresh()->token))->assertStatus(200);
    }

    public function test_un_usuario_no_puede_tocar_participantes_de_otro(): void
    {
        ['juan' => $juan] = $this->escenario();
        $otro = User::factory()->create();

        $this->actingAs($otro)->get(route('participants.edit', $juan))->assertForbidden();
        $this->actingAs($otro)->delete(route('participants.destroy', $juan))->assertForbidden();
        $this->assertDatabaseHas('participants', ['id' => $juan->id]);
    }

    public function test_comando_de_recordatorios_envia_emails(): void
    {
        Mail::fake();
        $this->escenario();

        $this->artisan('recordatorios:enviar', ['--todas' => true, '--pausa' => 0])
            ->assertSuccessful();

        Mail::assertSent(ResumenCuotasMail::class);          // al dueño
        Mail::assertSent(CuotasParticipanteMail::class, 2);   // a Juan y Ana
    }

    public function test_paginas_principales_responden_ok(): void
    {
        ['user' => $user, 'purchase' => $purchase] = $this->escenario();

        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
        $this->actingAs($user)->get(route('participants.index'))->assertStatus(200);
        $this->actingAs($user)->get(route('purchases.index'))->assertStatus(200);
        $this->actingAs($user)->get(route('purchases.show', $purchase))->assertStatus(200);
        $this->actingAs($user)->get(route('tracking.index'))->assertStatus(200);
    }
}
