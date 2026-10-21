<?php

namespace App\Models;

use App\Enums\EstadoPedido;
use App\Observers\PedidoObserver;
use Carbon\CarbonInterface;
use Database\Factories\PedidoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cliente_id', 'total', 'fecha', 'estado'])]
#[ObservedBy(PedidoObserver::class)]
class Pedido extends Model
{
    /** @use HasFactory<PedidoFactory> */
    use HasFactory;

    /**
     * Valores por defecto de los atributos (coinciden con la migración).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => EstadoPedido::Pendiente->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'costo_envio' => 'decimal:2',
            'fecha' => 'date',
            'estado' => EstadoPedido::class,
        ];
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Filtra los pedidos por estado.
     */
    #[Scope]
    protected function estado(Builder $query, EstadoPedido $estado): void
    {
        $query->where('estado', $estado);
    }

    /**
     * Filtra los pedidos cuya fecha cae dentro del mes de la fecha dada.
     */
    #[Scope]
    protected function delMes(Builder $query, CarbonInterface $fecha): void
    {
        $query->whereBetween('fecha', [
            $fecha->copy()->startOfMonth(),
            $fecha->copy()->endOfMonth(),
        ]);
    }

    public function estaEntregado(): bool
    {
        return $this->estado === EstadoPedido::Entregado;
    }
}
