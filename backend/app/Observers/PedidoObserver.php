<?php

namespace App\Observers;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Services\TransportadoraService;

class PedidoObserver
{
    public function __construct(private TransportadoraService $transportadora) {}

    /**
     * Cotiza el envío justo antes de guardar un pedido que pasa a "despachado".
     * Si la cotización falla, la excepción impide que el cambio de estado se guarde.
     */
    public function saving(Pedido $pedido): void
    {
        if ($pedido->isDirty('estado') && $pedido->estado === EstadoPedido::Despachado) {
            $pedido->costo_envio = $this->transportadora->cotizar($pedido);
        }
    }
}
