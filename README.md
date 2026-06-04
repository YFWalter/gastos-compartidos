# Gastos Compartidos

Aplicación web para **registrar compras en cuotas y dividirlas entre varias personas**, llevando el control de cuánto debe pagar cada una mes a mes y enviando recordatorios por email al acercarse el vencimiento de cada cuota.

> Caso de uso típico: comprás algo en varias cuotas junto a otra persona (un familiar, un amigo) y querés que quede registrado quién paga qué parte de cada cuota y cuándo.

## ¿Cómo funciona?

1. **Participantes** — cargás los contactos entre los que se reparten los gastos (no necesitan cuenta; solo un email para recibir los avisos).
2. **Compra** — registrás una compra (descripción, monto total, cantidad de cuotas y fecha de la primera) y definís el **reparto por porcentaje** entre los participantes.
3. **Cuotas** — el sistema genera automáticamente las cuotas con sus vencimientos mensuales y calcula **cuánto le toca a cada participante en cada cuota** (con redondeo exacto, sin centavos perdidos).
4. **Seguimiento** — marcás como pagada la parte de cada participante (o la cuota completa) y ves el estado general en el dashboard y en la vista de seguimiento por mes.
5. **Recordatorios** — un comando programado envía por email un resumen al dueño de la compra y un aviso a cada participante con lo que debe.

## Stack

- **Laravel 11** (PHP 8.2+)
- **Laravel Breeze** para la autenticación (Blade + Tailwind CSS + Alpine.js)
- **MySQL** (en desarrollo se usó MariaDB vía XAMPP)
- **Vite** para el compilado de assets

## Requisitos

- PHP 8.2 o superior
- Composer
- Node.js + npm
- MySQL

## Instalación

```bash
# 1. Clonar e instalar dependencias
git clone https://github.com/YFWalter/gastos-compartidos.git
cd gastos-compartidos
composer install
npm install

# 2. Configurar el entorno
cp .env.example .env
php artisan key:generate

# 3. Configurar la base de datos en el .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
#    y luego correr las migraciones
php artisan migrate

# 4. Levantar el entorno de desarrollo (en dos terminales)
php artisan serve      # backend  -> http://127.0.0.1:8000
npm run dev            # assets (Vite)
```

## Configuración de emails

Los recordatorios se envían por SMTP. Configurá tus credenciales en el `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io   # en desarrollo
MAIL_PORT=2525
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_password
MAIL_FROM_ADDRESS="no-reply@gastos-compartidos.test"
```

## Recordatorios por email

El envío se hace con un comando de Artisan:

```bash
# Avisar de las cuotas que vencen dentro de los próximos N días (default 3)
php artisan recordatorios:enviar --dias=3

# Enviar avisos de TODAS las cuotas pendientes, sin importar la fecha (útil para probar)
php artisan recordatorios:enviar --todas

# Pausa en segundos entre cada email (útil con proveedores con límite de envío, ej. Mailtrap free)
php artisan recordatorios:enviar --todas --pausa=15
```

En producción, el comando se ejecuta automáticamente todos los días a las 08:00 mediante el **scheduler** de Laravel. Para que funcione, agregá un cron en el servidor que corra cada minuto:

```bash
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## Estado

MVP funcional: gestión de participantes, compras con generación de cuotas, seguimiento de pagos y recordatorios por email.
