# Hosting MAE Consultas en cPanel Que Nube (un código, N clientes)

Documento interno. El CEA **no** instala PHP ni recibe FTP.

## Modelo

- Document root: `consultas/public/` (código, `config/`, `branding/` y secrets quedan **fuera** de la web).
- PHP 8.3 (mismo cPanel que MAE).
- Las bases MySQL son las de **mae-v8** (un user/BD por cliente). No hay BD propia de consultas.
- Tenant = `HTTP_HOST` → `config/tenants.php`.
- Secretos BD: `MAE_SECRETS_PATH` apunta a `mae/config/tenants.secrets.php` (un solo archivo). Fallback: `consultas/config/tenants.secrets.php`.
- Features: `branding/<id>/features.json`.
- Fotos y convenios: `MAE_TENANTS_ROOT` = `mae/public/tenants/<id>/fotos|convenios`.
- Kill-switch: `'active' => false` en `config/tenants.php` o quitar el addon domain.

## Destino DNS: `consultas.quenube.com`

En el cPanel Que Nube:

1. Subdominio `consultas` + `quenube.com`, document root = `consultas/public`.
2. AutoSSL para `consultas.quenube.com` (destino CNAME de los CEA).
3. Showcase: subdominio `democonsultas` + `quenube.com`, mismo document root.

La URL pública del demo es **`https://democonsultas.quenube.com`**.

## Por cada CEA

1. Addon domain del FQDN **de consulta** al **mismo** `consultas/public`.
2. `config/tenants.php`: host → `client_id` (mismos IDs que mae-v8: `demo`, `autolider`, `gem`, `mission-zero`).
3. DNS del CEA: CNAME del FQDN hacia `consultas.quenube.com`.

Hosts iniciales:

| FQDN | CLIENT_ID |
|------|-----------|
| democonsultas.quenube.com | demo |
| consultas.quenube.com | demo |
| cursos.autolider.com.co | autolider |
| consultas.grupoeducativodelmeta.com | gem |
| consultas.missionzero.example | mission-zero |

## Conflicto con mae-v8

Un FQDN solo puede ser addon de un document root. Los hosts de esta tabla **no** deben aparecer en `mae-v8/config/tenants.php`. MAE usa hosts distintos (`mae.quenube.com`, `mae.autolider.com.co`, etc.).

## .env del cPanel (consultas)

No lleva `DB_*` de clientes. Ejemplo:

```env
APP_KEY=...
MAE_SECRETS_PATH=/home/USER/mae/config/tenants.secrets.php
MAE_TENANTS_ROOT=/home/USER/mae/public/tenants
MAE_FORCE_HTTPS=true
```

Copiar `.env` al padre de `consultas/` o a `consultas/.env` (fuera del docroot).

## Deploy

GitHub Actions: **Deploy consultas hosted** (un FTPS). Secrets: `SFTP_SERVER_CONSULTAS`, `SFTP_USER_CONSULTAS`, `SFTP_PASSWORD_CONSULTAS`.

Usuario FTPS chroot a la carpeta `consultas/`.

No se suben: `docs/`, `scripts/`, `.github/`, `tenants.secrets.php`, `.env*`.

## Local

```env
LICENSE_SKIP=true
MAE_SKIP_PHP_CHECK=true
CLIENT_ID=demo
DB_HOST=localhost
DB_NAME=...
DB_USER=...
DB_PASS=...
APP_KEY=...
```

Docroot de desarrollo: `public/`.
