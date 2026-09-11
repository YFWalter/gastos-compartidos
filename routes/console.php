<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generación de cargos de servicios recurrentes: todos los días a las 07:00 revisa
// si algún servicio activo necesita el cargo del próximo mes (por defecto, 5 días
// antes del vencimiento). Corre antes que los recordatorios para que el cargo del
// mes ya exista cuando se avisa por email.
Schedule::command('servicios:generar-cargos')->dailyAt('07:00');

// Recordatorios de cuotas y cargos: todos los días a las 08:00 avisa de los que vencen
// dentro de los próximos 3 días. La pausa de 15s entre emails respeta el límite
// de envío por segundo de Mailtrap (subila/bajala según el proveedor de producción).
// En cPanel basta un cron que corra `php artisan schedule:run` cada minuto.
Schedule::command('recordatorios:enviar --dias=3 --pausa=15')->dailyAt('08:00');
