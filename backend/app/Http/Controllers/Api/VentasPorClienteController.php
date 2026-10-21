<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VentasPorClienteController extends Controller
{
    /**
     * Total vendido por cliente en el mes actual, de mayor a menor.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        $mes = now();
        $delMes = fn (Builder $query) => $query->delMes($mes);

        $clientes = Cliente::query()
            ->whereHas('pedidos', $delMes)
            ->withCount(['pedidos' => $delMes])
            ->withSum(['pedidos as total_vendido' => $delMes], 'total')
            ->orderByDesc('total_vendido')
            ->orderBy('nombre')
            ->get();

        return ClienteResource::collection($clientes)->additional([
            'mes' => $mes->format('Y-m'),
        ]);
    }
}
