<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePedidoRequest;
use App\Http\Requests\UpdatePedidoRequest;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PedidoController extends Controller
{
    /**
     * Listado paginado de pedidos con su cliente, filtrable por estado.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoPedido::class)],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $pedidos = Pedido::query()
            ->with('cliente')
            ->when($request->enum('estado', EstadoPedido::class), fn ($query, $estado) => $query->estado($estado))
            ->latest('fecha')
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PedidoResource::collection($pedidos);
    }

    public function store(StorePedidoRequest $request): PedidoResource
    {
        $pedido = Pedido::create($request->validated());

        return PedidoResource::make($pedido->refresh()->load('cliente'));
    }

    public function show(Pedido $pedido): PedidoResource
    {
        return PedidoResource::make($pedido->load('cliente'));
    }

    public function update(UpdatePedidoRequest $request, Pedido $pedido): PedidoResource
    {
        Gate::authorize('update', $pedido);

        $pedido->update($request->validated());

        return PedidoResource::make($pedido->load('cliente'));
    }

    public function destroy(Pedido $pedido): Response
    {
        Gate::authorize('delete', $pedido);

        $pedido->delete();

        return response()->noContent();
    }
}
