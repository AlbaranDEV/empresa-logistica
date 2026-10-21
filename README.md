# Empresa Logística

Monorepo con backend en **Laravel 13** y frontend en **Angular 22**, contenerizado con Docker.

## Estructura

```
empresa-logistica/
├── backend/              # API Laravel
├── frontend/             # App Angular
├── docker/
│   ├── nginx/            # Configuración de Nginx (sirve Laravel)
│   ├── php/              # Imagen PHP-FPM 8.4 + Composer + extensiones
│   └── node/             # Imagen Node 22 para el dev server de Angular
├── docker-compose.yml
└── .env.example          # Puertos y credenciales de MySQL
```

## Servicios

| Servicio | Contenedor          | URL / Puerto              |
|----------|---------------------|---------------------------|
| angular  | logistica-angular   | http://localhost:4200     |
| nginx    | logistica-nginx     | http://localhost:8000     |
| laravel  | logistica-laravel   | PHP-FPM (interno, :9000)  |
| mysql    | logistica-mysql     | localhost:3306            |
| redis    | logistica-redis     | localhost:6379            |

Angular redirige las peticiones `/api/*` a Laravel con un proxy (`frontend/proxy.conf.json`), así que no hace falta configurar CORS en desarrollo.

## Primer arranque

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec laravel php artisan migrate
```

En el primer arranque se ejecutan solos `composer install` y `npm install`.

Para comprobar que todo funciona: http://localhost:8000/api/health

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
