<?php

namespace Tests\Feature\Pedidos;

use App\Enums\EstadoPedido;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CostoEnvioTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://tarifas.transportadora.test/v1/cotizar';

    public function test_al_despachar_cotiza_y_guarda_el_costo_de_envio(): void
    {
        Http::fake([self::URL => Http::response(['costo' => 23500.75])]);

        $cliente = Cliente::factory()->create(['ciudad' => 'Armenia']);
        $pedido = Pedido::factory()->for($cliente)->create(['total' => 300000]);

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/pedidos/{$pedido->id}", ['estado' => 'despachado'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'despachado')
            ->assertJsonPath('data.costo_envio', 23500.75);

        $this->assertEquals(23500.75, $pedido->fresh()->costo_envio);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === self::URL
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer token-de-prueba')
            && $request['referencia'] === $pedido->id
            && $request['destino'] === 'Armenia'
            && $request['valor_declarado'] == 300000);
    }

    public function test_no_consulta_la_transportadora_en_otros_cambios(): void
    {
        Http::fake();
        $usuario = User::factory()->create();

        $pendiente = Pedido::factory()->create();
        $this->actingAs($usuario)->patchJson("/api/pedidos/{$pendiente->id}", ['total' => 10])->assertOk();

        $despachado = Pedido::factory()->despachado()->createQuietly(['costo_envio' => 9000]);
        $this->actingAs($usuario)->patchJson("/api/pedidos/{$despachado->id}", ['estado' => 'entregado'])->assertOk();
        $this->actingAs($usuario)->postJson('/api/pedidos', [
            'cliente_id' => $pendiente->cliente_id,
            'total' => 500,
            'fecha' => '2026-10-01',
        ])->assertCreated();

        Http::assertNothingSent();
        $this->assertNull($pendiente->fresh()->costo_envio);
        $this->assertEquals(9000, $despachado->fresh()->costo_envio);
    }

    public function test_si_la_transportadora_falla_el_pedido_no_cambia_de_estado(): void
    {
        Http::fake([self::URL => Http::response(['message' => 'Unauthorized'], 401)]);

        $pedido = Pedido::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/pedidos/{$pedido->id}", ['estado' => 'despachado'])
            ->assertStatus(502)
            ->assertJsonPath('message', 'No fue posible cotizar el envío con la transportadora.');

        $this->assertSame(EstadoPedido::Pendiente, $pedido->fresh()->estado);
        $this->assertNull($pedido->fresh()->costo_envio);
        Http::assertSentCount(1);
    }

    public function test_reintenta_ante_errores_transitorios_de_la_transportadora(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push(['message' => 'Service Unavailable'], 503)
            ->push(['costo' => 12000])]);

        $pedido = Pedido::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/pedidos/{$pedido->id}", ['estado' => 'despachado'])
            ->assertOk()
            ->assertJsonPath('data.costo_envio', 12000);

        Http::assertSentCount(2);
    }

    public function test_rechaza_una_cotizacion_invalida(): void
    {
        Http::fake([self::URL => Http::response(['tarifa' => 'gratis'])]);

        $pedido = Pedido::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/pedidos/{$pedido->id}", ['estado' => 'despachado'])
            ->assertStatus(502)
            ->assertJsonPath('message', 'La transportadora devolvió una cotización inválida.');

        $this->assertSame(EstadoPedido::Pendiente, $pedido->fresh()->estado);
    }

    public function test_sin_credencial_no_se_puede_despachar(): void
    {
        config(['services.transportadora.token' => null]);
        Http::fake();

        $pedido = Pedido::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/pedidos/{$pedido->id}", ['estado' => 'despachado'])
            ->assertStatus(502);

        Http::assertNothingSent();
        $this->assertSame(EstadoPedido::Pendiente, $pedido->fresh()->estado);
    }
}
