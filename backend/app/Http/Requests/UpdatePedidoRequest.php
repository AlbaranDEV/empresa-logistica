<?php

namespace App\Http\Requests;

use App\Enums\EstadoPedido;
use Illuminate\Validation\Rule;

/**
 * Permite actualizaciones parciales (PATCH), por ejemplo solo el estado.
 */
class UpdatePedidoRequest extends StorePedidoRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = array_map(
            fn (array $reglas) => ['sometimes', ...$reglas],
            parent::rules(),
        );

        return [
            ...$rules,
            'estado' => ['sometimes', 'required', Rule::enum(EstadoPedido::class)],
        ];
    }
}
