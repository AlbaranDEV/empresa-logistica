<?php

namespace Tests\Feature\Pedidos;

use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListarPedidosTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtra_por_estado_e_incluye_los_datos_del_cliente(): void
    {
        $pendiente = Pedido::factory()->create();
        Pedido::factory()->despachado()->createQuietly();
        Pedido::factory()->entregado()->createQuietly();

        $this->getJson('/api/pedidos?estado=pendiente')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendiente->id)
            ->assertJsonPath('data.0.estado', 'pendiente')
            ->assertJsonPath('data.0.cliente.nombre', $pendiente->cliente->nombre)
            ->assertJsonPath('data.0.cliente.ciudad', $pendiente->cliente->ciudad);
    }

    public function test_sin_filtro_devuelve_todos_los_pedidos_paginados(): void
    {
        Pedido::factory()->count(3)->create();
        Pedido::factory()->entregado()->createQuietly();

        $this->getJson('/api/pedidos?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 4)
            ->assertJsonStructure([
                'data' => [['id', 'cliente_id', 'cliente' => ['id', 'nombre', 'ciudad'], 'total', 'fecha', 'estado', 'costo_envio']],
                'links',
                'meta',
            ]);
    }

    public function test_rechaza_un_estado_invalido(): void
    {
        $this->getJson('/api/pedidos?estado=cancelado')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estado');
    }

    public function test_muestra_un_pedido(): void
    {
        $pedido = Pedido::factory()->create();

        $this->getJson("/api/pedidos/{$pedido->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $pedido->id)
            ->assertJsonPath('data.cliente.id', $pedido->cliente_id);
    }
}
