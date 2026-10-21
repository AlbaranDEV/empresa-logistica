<?php

namespace Tests\Feature\Reportes;

use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentasPorClienteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 20));
    }

    public function test_suma_las_ventas_del_mes_actual_ordenadas_de_mayor_a_menor(): void
    {
        $menor = Cliente::factory()->create(['nombre' => 'Menor']);
        $mayor = Cliente::factory()->create(['nombre' => 'Mayor']);
        $sinVentas = Cliente::factory()->create(['nombre' => 'Sin ventas del mes']);

        Pedido::factory()->for($menor)->create(['total' => 100, 'fecha' => '2026-10-01']);
        Pedido::factory()->for($menor)->entregado()->createQuietly(['total' => 50, 'fecha' => '2026-10-31']);
        Pedido::factory()->for($mayor)->create(['total' => 1000, 'fecha' => '2026-10-20']);

        // Fuera del mes actual: no deben sumar
        Pedido::factory()->for($menor)->create(['total' => 9999, 'fecha' => '2026-09-30']);
        Pedido::factory()->for($sinVentas)->create(['total' => 9999, 'fecha' => '2026-11-01']);

        $this->getJson('/api/reportes/ventas-por-cliente')
            ->assertOk()
            ->assertJsonPath('mes', '2026-10')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nombre', 'Mayor')
            ->assertJsonPath('data.0.total_vendido', 1000)
            ->assertJsonPath('data.0.cantidad_pedidos', 1)
            ->assertJsonPath('data.1.nombre', 'Menor')
            ->assertJsonPath('data.1.total_vendido', 150)
            ->assertJsonPath('data.1.cantidad_pedidos', 2);
    }

    public function test_devuelve_una_lista_vacia_si_no_hay_ventas_en_el_mes(): void
    {
        Pedido::factory()->create(['fecha' => '2026-08-15']);

        $this->getJson('/api/reportes/ventas-por-cliente')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
