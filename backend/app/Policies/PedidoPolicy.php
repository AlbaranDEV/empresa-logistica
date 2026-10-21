<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PedidoPolicy
{
    /**
     * Un pedido entregado queda bloqueado; solo un administrador puede corregirlo.
     */
    public function update(User $user, Pedido $pedido): Response
    {
        return ! $pedido->estaEntregado() || $user->esAdmin()
            ? Response::allow()
            : Response::deny('Los pedidos entregados no se pueden modificar. Solo un administrador puede corregirlos.');
    }

    public function delete(User $user, Pedido $pedido): Response
    {
        return $this->update($user, $pedido);
    }
}
