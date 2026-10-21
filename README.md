# Empresa Logística

Monorepo con backend en **Laravel 13** y frontend en **Angular 22**, contenerizado con Docker.
Incluye un módulo de pedidos: listado con filtro por estado, creación y edición, cambio de estado,
reporte de ventas por cliente y cálculo del costo de envío con la API de la transportadora.

## Estructura

```
empresa-logistica/
├── backend/                  # API Laravel
├── frontend/                 # App Angular
├── docker/
│   ├── nginx/                # Configuración de Nginx (sirve Laravel)
│   ├── php/                  # Imagen PHP-FPM 8.4 + Composer + extensiones
│   ├── node/                 # Imagen Node 22 para el dev server de Angular
│   └── transportadora-mock/  # Simulador local de la API de tarifas
├── docker-compose.yml
└── .env.example              # Puertos, credenciales de MySQL y de la transportadora
```

## Servicios

| Servicio            | Contenedor                    | URL / Puerto              |
|---------------------|-------------------------------|---------------------------|
| angular             | logistica-angular             | http://localhost:4200     |
| nginx               | logistica-nginx               | http://localhost:8000     |
| laravel             | logistica-laravel             | PHP-FPM (interno, :9000)  |
| mysql               | logistica-mysql               | localhost:3306            |
| redis               | logistica-redis               | localhost:6379            |
| transportadora-mock | logistica-transportadora-mock | interno (solo desarrollo) |

Angular redirige las peticiones `/api/*` a Laravel con un proxy (`frontend/proxy.conf.json`), así que no hace falta configurar CORS en desarrollo.

## Primer arranque

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec laravel php artisan migrate --seed
```

En el primer arranque se ejecutan solos `composer install` y `npm install`.
Para comprobar que todo funciona: http://localhost:8000/api/health

### Usuarios de prueba (seeder)

| Rol      | Correo                    | Contraseña |
|----------|---------------------------|------------|
| admin    | admin@logistica.test      | password   |
| operador | operador@logistica.test   | password   |

## Módulo de pedidos

### Modelo de datos

- `clientes`: `id`, `nombre`, `ciudad`
- `pedidos`: `id`, `cliente_id`, `total`, `fecha`, `estado` (`pendiente` | `despachado` | `entregado`, por defecto `pendiente`), `costo_envio`
- `users.rol`: `admin` | `operador`

### API

| Método | Ruta                               | Auth  | Descripción                                             |
|--------|------------------------------------|-------|---------------------------------------------------------|
| POST   | `/api/login`                       | —     | Devuelve un token de Sanctum                            |
| POST   | `/api/logout`                      | Token | Revoca el token actual                                  |
| GET    | `/api/user`                        | Token | Usuario autenticado                                     |
| GET    | `/api/pedidos?estado=pendiente`    | —     | Pedidos paginados con su cliente, filtro opcional       |
| GET    | `/api/pedidos/{id}`                | —     | Detalle de un pedido                                    |
| POST   | `/api/pedidos`                     | Token | Crea un pedido (`cliente_id`, `total`, `fecha`)         |
| PATCH  | `/api/pedidos/{id}`                | Token | Actualiza datos y/o `estado`                            |
| DELETE | `/api/pedidos/{id}`                | Token | Elimina un pedido                                       |
| GET    | `/api/clientes`                    | —     | Clientes (para el formulario)                           |
| GET    | `/api/reportes/ventas-por-cliente` | —     | Total vendido por cliente en el mes actual, de mayor a menor |

Las rutas de solo lectura son públicas; las que modifican datos requieren `Authorization: Bearer <token>`.

### Reglas de negocio

- **Costo de envío**: cuando un pedido pasa a `despachado`, `PedidoObserver` llama a
  `TransportadoraService`, que hace `POST {TRANSPORTADORA_URL}/cotizar` con
  `{ referencia, destino, valor_declarado }` y guarda el campo `costo` de la respuesta en `costo_envio`.
  Si la transportadora falla, responde con un costo inválido o no hay credencial, la API
  devuelve 502 y el pedido **no** cambia de estado. Solo se reintentan los errores de red y los 5xx.
- **Pedidos entregados**: `PedidoPolicy` impide modificarlos o eliminarlos (403), salvo a un usuario con rol `admin`.

### Transportadora

La credencial se configura en el `.env` de la raíz:

```bash
TRANSPORTADORA_URL=https://tarifas.transportadora.test/v1
TRANSPORTADORA_TOKEN=<token entregado por la transportadora>
```

Por defecto apunta al simulador local `transportadora-mock`, que responde con un costo fijo de 18.500 COP.
Si la API real usa otro formato de petición o de respuesta, solo hay que ajustar `backend/app/Services/TransportadoraService.php`.

## Tests

```bash
docker compose exec laravel php artisan test     # backend (PHPUnit, SQLite en memoria)
docker compose exec angular npx ng test --watch=false   # frontend (Vitest)
```

Los tests del backend usan SQLite en memoria (forzado en `phpunit.xml`) y `Http::fake`, así que no tocan la
base de datos de desarrollo ni llaman a APIs externas.

## Comandos útiles

```bash
docker compose exec laravel php artisan <comando>
docker compose exec laravel composer require <paquete>
docker compose exec angular npx ng generate component <nombre>
docker compose exec angular npm install <paquete>
docker compose logs -f <servicio>
docker compose down        # detener
docker compose down -v     # detener y borrar datos (MySQL, Redis, node_modules)
```
