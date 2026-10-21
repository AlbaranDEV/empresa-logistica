<?php

namespace Tests\Feature\Pedidos;

use App\Enums\EstadoPedido;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GestionarPedidosTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_un_pedido_en_estado_pendiente(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/pedidos', [
                'cliente_id' => $cliente->id,
                'total' => 150000.50,
                'fecha' => '2026-10-15',
            ])
            ->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.total', 150000.5)
            ->assertJsonPath('data.cliente.id', $cliente->id);

        $pedido = Pedido::sole();
        $this->assertSame($cliente->id, $pedido->cliente_id);
        $this->assertSame('2026-10-15', $pedido->fecha->toDateString());
        $this->assertSame(EstadoPedido::Pendiente, $pedido->estado);
    }

    public function test_el_estado_por_defecto_en_base_de_datos_es_pendiente(): void
    {
        $cliente = Cliente::factory()->create();

        $id = DB::table('pedidos')->insertGetId([
            'cliente_id' => $cliente->id,
            'total' => 1000,
            'fecha' => '2026-10-01',
        ]);

        $this->assertSame('pendiente', DB::table('pedidos')->where('id', $id)->value('estado'));
    }

    public function test_valida_los_datos_al_crear(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/pedidos', [
                'cliente_id' => 999,
                'total' => -5,
                'fecha' => '15/10/2026',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cliente_id', 'total', 'fecha']);
    }

    public function test_actualiza_un_pedido(): void
    {
        $pedido = Pedido::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/pedidos/{$pedido->id}", ['total' => 99000])
            ->assertOk()
            ->assertJsonPath('data.total', 99000);

        $this->assertEquals(99000, $pedido->fresh()->total);
    }

    public function test_cambia_el_estado_a_entregado(): void
    {
        $pedido = Pedido::factory()->despachado()->createQuietly();

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/pedidos/{$pedido->id}", ['estado' => 'entregado'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'entregado');

        $this->assertSame(EstadoPedido::Entregado, $pedido->fresh()->estado);
    }

    public function test_rechaza_un_estado_inexistente(): void
    {
        $pedido = Pedido::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patchJson("/api/pedidos/{$pedido->id}", ['estado' => 'perdido'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estado');
    }

    public function test_elimina_un_pedido(): void
    {
        $pedido = Pedido::factory()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/pedidos/{$pedido->id}")
            ->assertNoContent();

        $this->assertModelMissing($pedido);
    }

    public function test_las_escrituras_requieren_autenticacion(): void
    {
        $pedido = Pedido::factory()->create();

        $this->postJson('/api/pedidos', [])->assertUnauthorized();
        $this->patchJson("/api/pedidos/{$pedido->id}", ['total' => 1])->assertUnauthorized();
        $this->deleteJson("/api/pedidos/{$pedido->id}")->assertUnauthorized();
    }
}
