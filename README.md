# Consulta de Certificados MAE

Aplicación pública para consultar certificados por identificación o número de certificado. Infraestructura hosted: **un cPanel Que Nube, un código, N bases** (las mismas MySQL de mae-v8, una por cliente).

## Requisitos

- PHP 8.3.x
- MySQL/MariaDB (bases de mae-v8)
- Document root: `public/`

## Local

1. Copiar `.env.example` a `.env`
2. `LICENSE_SKIP=true`, `MAE_SKIP_PHP_CHECK=true`, `CLIENT_ID=demo` y `DB_*` de una BD de desarrollo
3. Apuntar el servidor a `public/` (p. ej. `php -S localhost:8080 -t public`)

## Producción

Ver [`docs/HOSTING_QUENUBE.md`](docs/HOSTING_QUENUBE.md): addon domains al mismo `consultas/public`, `MAE_SECRETS_PATH` hacia `mae/config/tenants.secrets.php`, fotos en `mae/public/tenants/<id>/`.

## Estructura

```
mae-consultas/
├── public/                # Document root (index, certificado, carnet, assets)
├── app/                   # Núcleo PHP (fuera de la web)
├── config/tenants.php     # Host → client_id
├── branding/<id>/         # features.json + imágenes PDF
└── fpdf/
```
