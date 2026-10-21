<?php

namespace Database\Factories;

use App\Enums\EstadoPedido;
use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pedido>
 */
class PedidoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'total' => fake()->randomFloat(2, 50_000, 5_000_000),
            'fecha' => fake()->dateTimeBetween('-2 months')->format('Y-m-d'),
            'estado' => EstadoPedido::Pendiente,
        ];
    }

    public function despachado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoPedido::Despachado,
            'costo_envio' => fake()->randomFloat(2, 8_000, 60_000),
        ]);
    }

    public function entregado(): static
    {
        return $this->despachado()->state([
            'estado' => EstadoPedido::Entregado,
        ]);
    }
}
