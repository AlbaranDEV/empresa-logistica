<?php

namespace App\Http\Resources;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pedido
 */
class PedidoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'cliente' => ClienteResource::make($this->whenLoaded('cliente')),
            'total' => (float) $this->total,
            'fecha' => $this->fecha->toDateString(),
            'estado' => $this->estado,
            'costo_envio' => $this->costo_envio === null ? null : (float) $this->costo_envio,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
