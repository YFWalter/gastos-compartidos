# Deploy a Hostinger (SSH + GitHub Actions)

Mismo esquema que el proyecto `agenda`: la app vive en una carpeta **separada** del docroot,
y `public_html/index.php` hace `require` de la app. El deploy es automático en cada push a `main`
(`.github/workflows/deploy.yml`): corre los tests, compila los assets y los sube por SSH.

Reemplazá en todo el documento:
- `<DOMINIO>` = `lightcoral-goose-780379.hostingersite.com`
- `<USER>` = usuario SSH de Hostinger (ej. `u540878453`)
- Ruta base: `/home/<USER>/domains/<DOMINIO>`

## 1) Base de datos (hPanel → Bases de datos MySQL)
Crear base + usuario + contraseña. Anotarlos para el `.env`.

## 2) Setup inicial por SSH (una sola vez)

```bash
cd ~/domains/<DOMINIO>
git clone https://github.com/YFWalter/gastos-compartidos.git gastos-compartidos
cd gastos-compartidos

composer install --no-dev --optimize-autoloader

cp .env.example .env
php artisan key:generate
# Editar .env (ver sección 3) y luego:
php artisan migrate --force
php artisan optimize:clear
```

## 3) `.env` de producción (valores clave)

```env
APP_NAME=DiviGastos
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<DOMINIO>
APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...     # de hPanel
DB_USERNAME=...     # de hPanel
DB_PASSWORD=...     # de hPanel

# SMTP real de producción (no Mailtrap)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="no-reply@<DOMINIO>"
```

## 4) Docroot: apuntar `public_html` a la app

En `~/domains/<DOMINIO>/public_html`:

1. Copiar el `.htaccess` de Laravel:
   ```bash
   cp ~/domains/<DOMINIO>/gastos-compartidos/public/.htaccess ~/domains/<DOMINIO>/public_html/.htaccess
   ```
2. Reemplazar `public_html/index.php` por este "shim" (apunta a la app hermana):

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$base = __DIR__.'/../gastos-compartidos';

if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $base.'/vendor/autoload.php';

(require_once $base.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
```

> Los assets compilados (`build/`) los sincroniza el workflow a `public_html/build` en cada deploy.

## 5) Secrets en GitHub (repo → Settings → Secrets and variables → Actions)

Los de SSH son los **mismos** que en `agenda` (misma cuenta de Hostinger):

| Secret | Valor |
|--------|-------|
| `SSH_HOST` | (mismo que agenda) |
| `SSH_PORT` | `65002` |
| `SSH_USERNAME` | `<USER>` |
| `SSH_KEY` | clave privada ed25519 (la misma de agenda) |
| `REMOTE_APP` | `/home/<USER>/domains/<DOMINIO>/gastos-compartidos` |
| `REMOTE_BUILD` | `/home/<USER>/domains/<DOMINIO>/public_html/build` |

## 6) Cron de recordatorios (hPanel → Cron Jobs)

Cada minuto:

```
* * * * * php /home/<USER>/domains/<DOMINIO>/gastos-compartidos/artisan schedule:run >> /dev/null 2>&1
```

(El scheduler ya tiene definido `recordatorios:enviar --dias=3 --pausa=15` a las 08:00.)

## 7) Primer deploy
Con el setup hecho y los secrets cargados, hacé push a `main` (o disparalo manual desde Actions).
El workflow correrá tests → build → deploy.
