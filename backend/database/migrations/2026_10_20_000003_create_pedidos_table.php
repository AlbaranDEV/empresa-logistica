<?php

use App\Enums\EstadoPedido;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->decimal('total', 12, 2);
            $table->date('fecha');
            $table->enum('estado', array_column(EstadoPedido::cases(), 'value'))
                ->default(EstadoPedido::Pendiente->value);
            $table->decimal('costo_envio', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha']);
            $table->index(['cliente_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
