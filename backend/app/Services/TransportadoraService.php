<?php

namespace App\Services;

use App\Exceptions\TransportadoraException;
use App\Models\Pedido;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente HTTP de la API de tarifas de la transportadora.
 */
class TransportadoraService
{
    public function __construct(
        #[Config('services.transportadora.url')] private string $url,
        #[Config('services.transportadora.token')] private ?string $token,
        #[Config('services.transportadora.timeout')] private int $timeout,
    ) {}

    /**
     * Cotiza el costo de envío de un pedido.
     *
     * @throws TransportadoraException
     */
    public function cotizar(Pedido $pedido): float
    {
        if (blank($this->token)) {
            throw TransportadoraException::sinCredencial();
        }

        try {
            $costo = Http::baseUrl($this->url)
                ->withToken($this->token)
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(2, 200, fn (Throwable $e) => $this->esErrorTransitorio($e), throw: false)
                ->post('cotizar', [
                    'referencia' => $pedido->id,
                    'destino' => $pedido->cliente()->value('ciudad'),
                    'valor_declarado' => (float) $pedido->total,
                ])
                ->throw()
                ->json('costo');
        } catch (ConnectionException|RequestException $e) {
            throw TransportadoraException::fallo($e);
        }

        if (! is_numeric($costo) || $costo < 0) {
            throw TransportadoraException::respuestaInvalida();
        }

        return (float) $costo;
    }

    /**
     * Solo se reintentan los fallos de red y los errores 5xx de la transportadora.
     */
    private function esErrorTransitorio(Throwable $e): bool
    {
        return $e instanceof ConnectionException
            || ($e instanceof RequestException && $e->response->serverError());
    }
}
