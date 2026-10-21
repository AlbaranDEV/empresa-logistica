<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

class TransportadoraException extends RuntimeException
{
    public static function sinCredencial(): self
    {
        return new self('No hay credencial configurada para la API de la transportadora (TRANSPORTADORA_TOKEN).');
    }

    public static function fallo(Throwable $previous): self
    {
        return new self('No fue posible cotizar el envío con la transportadora.', previous: $previous);
    }

    public static function respuestaInvalida(): self
    {
        return new self('La transportadora devolvió una cotización inválida.');
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 502);
    }
}
