<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedidoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'total' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
