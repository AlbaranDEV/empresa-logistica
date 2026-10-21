<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Sin eventos: los pedidos despachados de prueba no consultan la transportadora.
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuarios de prueba (contraseña: "password")
        User::factory()->admin()->create([
            'name' => 'Administrador',
            'email' => 'admin@logistica.test',
        ]);

        User::factory()->create([
            'name' => 'Operador',
            'email' => 'operador@logistica.test',
        ]);

        $clientes = collect([
            ['nombre' => 'Café del Quindío', 'ciudad' => 'Armenia'],
            ['nombre' => 'Ferretería El Tornillo', 'ciudad' => 'Armenia'],
            ['nombre' => 'Textiles Andinos', 'ciudad' => 'Medellín'],
            ['nombre' => 'Distribuidora La 80', 'ciudad' => 'Bogotá'],
            ['nombre' => 'Agroinsumos del Valle', 'ciudad' => 'Cali'],
            ['nombre' => 'Papelería Central', 'ciudad' => 'Pereira'],
        ])->map(fn (array $datos) => Cliente::create($datos));

        $clientes->each(function (Cliente $cliente) {
            $delMes = fn () => ['fecha' => fake()->dateTimeBetween(now()->startOfMonth(), now())->format('Y-m-d')];

            Pedido::factory()->for($cliente)->count(2)->state($delMes)->create();
            Pedido::factory()->for($cliente)->despachado()->state($delMes)->create();
            Pedido::factory()->for($cliente)->entregado()->count(2)->create();
        });
    }
}
