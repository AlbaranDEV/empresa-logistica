<?php

namespace Tests\Feature\Pedidos;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidosEntregadosTest extends TestCase
{
    use RefreshDatabase;

    private Pedido $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pedido = Pedido::factory()->entregado()->createQuietly(['total' => 1000]);
    }

    public function test_un_operador_no_puede_modificar_un_pedido_entregado(): void
    {
        $this->actingAs(User::factory()->create())
            ->patchJson("/api/pedidos/{$this->pedido->id}", ['total' => 5])
            ->assertForbidden()
            ->assertJsonPath('message', 'Los pedidos entregados no se pueden modificar. Solo un administrador puede corregirlos.');

        $this->assertEquals(1000, $this->pedido->fresh()->total);
    }

    public function test_un_operador_no_puede_eliminar_un_pedido_entregado(): void
    {
        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/pedidos/{$this->pedido->id}")
            ->assertForbidden();

        $this->assertModelExists($this->pedido);
    }

    public function test_un_administrador_puede_corregir_un_pedido_entregado(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->patchJson("/api/pedidos/{$this->pedido->id}", ['total' => 5, 'estado' => 'pendiente'])
            ->assertOk()
            ->assertJsonPath('data.total', 5)
            ->assertJsonPath('data.estado', 'pendiente');
    }

    public function test_un_administrador_puede_eliminar_un_pedido_entregado(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->deleteJson("/api/pedidos/{$this->pedido->id}")
            ->assertNoContent();

        $this->assertModelMissing($this->pedido);
    }
}
