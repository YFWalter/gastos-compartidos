# Gastos Compartidos ("DiviGastos") — Especificación y estado

> Registro de alcance para retomar el proyecto. Última actualización del contexto: 2026-09-11, verificado contra el código y los tests. **DISTINTO de `control-gastos`** (este es multi-persona; el otro es gastos personales).

## Qué es
Sistema web para **registrar gastos compartidos entre varias personas**, de dos tipos:
- **Compras en cuotas** — monto fijo dividido en N cuotas con fecha de fin conocida (ej. una compra en 6 cuotas).
- **Servicios recurrentes** — monto mensual que se repite indefinidamente hasta que se cancela (ej. Netflix, alquiler, internet).

En ambos casos el sistema controla cuánto debe pagar cada participante mes a mes y envía recordatorios por email al vencer cada cuota/cargo.

## Definiciones funcionales
- **Multi-usuario** (auth Breeze): cada usuario carga y ve SUS propias compras y servicios.
- **Participantes** = contactos del usuario (nombre, apellido, email, teléfono). No necesitan cuenta; reciben avisos por email.
- **Flujo compras:** cargar compra (descripción, monto total, N cuotas, fecha 1ª cuota) → definir división por % entre participantes → el sistema genera las N cuotas y calcula el monto de cada participante por cuota.
- **Flujo servicios:** cargar servicio (descripción, monto mensual, fecha del 1er vencimiento) → definir división por % (fija mientras el servicio esté activo) → el sistema genera el primer cargo al crearlo y uno nuevo cada mes, solo, hasta que se cancele. Cancelar detiene la generación futura pero no toca los cargos pendientes ya generados. El **monto mensual se puede editar** después de creado (ej. aumento de tarifa): el cambio aplica solo a los cargos que se generen de ahí en más, los ya generados quedan con el monto que tenían.
- **Aviso a participantes por compra/servicio:** cada compra y cada servicio tiene un toggle `avisar_participantes` (activado por defecto) para desactivar el email a los participantes de ese ítem puntual, para casos donde no revisan el correo. El dueño siempre recibe su resumen igual.

## Stack
- Laravel 11 (11.54) + Breeze (Blade + Tailwind + Alpine) + Vite
- PHP local 8.2.29 / hosting PHP 8.3
- MySQL en local y prod (local = MariaDB 10.4.32 vía XAMPP, base `gastos_compartidos`)
- Branding: APP_NAME=**DiviGastos**, locale `es`, logo Heroicons "receipt-percent"

## Modelo de datos
- `users` (Breeze) — dueños
- `participants` — user_id, nombre, apellido, email, telefono, **token** (link de invitado), es_titular. Accessor `nombre_completo`, `enlace_invitado`
- `purchases` — user_id, descripcion, monto_total, cantidad_cuotas, fecha_primera_cuota, notas, avisar_participantes
- `purchase_splits` — purchase_id, participant_id, porcentaje. unique(purchase_id, participant_id)
- `installments` — purchase_id, numero, vencimiento, monto, estado
- `installment_shares` — installment_id, participant_id, monto, estado, fecha_pago
- `services` — user_id, descripcion, monto_mensual, fecha_primer_vencimiento, notas, estado (activo/cancelado), avisar_participantes, cancelado_en
- `service_splits` — service_id, participant_id, porcentaje. unique(service_id, participant_id)
- `service_charges` — service_id, numero, vencimiento, monto, estado (el "cargo" mensual, mirror de `installments` pero sin cantidad fija)
- `service_charge_shares` — service_charge_id, participant_id, monto, estado, fecha_pago

## Repo / producción
- Repo: `https://github.com/YFWalter/gastos-compartidos.git`, rama `main`
- **EN PRODUCCIÓN y funcionando** (2026-06-08): `https://lightcoral-goose-780379.hostingersite.com` (Hostinger). CI/CD verde (push a main → tests + deploy).
- Usuario prueba (seeder): `test@example.com` / `password`

## Estado actual — MVP COMPLETO (las 4 etapas) + servicios recurrentes
1. ✅ **ABM Participantes** — ParticipantController (resource except show), todo filtrado por usuario, `abort_unless` 403.
2. ✅ **ABM Compras + generación de cuotas** — PurchaseController (transacción crea purchase+splits+cuotas). `app/Services/InstallmentGenerator.php` reparte centavos exacto (sin pérdidas). Edit solo descripcion+notas+avisar_participantes (no regenera). PurchaseRequest valida que los % sumen 100.
3. ✅ **Seguimiento de pagos** — toggle pagado/pendiente por share y por cuota/cargo completo (sincroniza estado). TrackingController (cuotas y cargos por mes, en secciones separadas). DashboardController (stats + próximos 5 vencimientos combinados).
4. ✅ **Recordatorios por email** — comando `php artisan recordatorios:enviar {--dias=3} {--todas} {--pausa=N}`. Mailables `ResumenCuotasMail` (dueño, con botón "Ir al seguimiento") y `CuotasParticipanteMail` (participante, con botón "Ver mis cuotas"). Incluye cuotas de compras Y cargos de servicios en el mismo envío. Respeta `avisar_participantes` de cada compra/servicio. Scheduler `dailyAt('08:00')`. EN PROD usa **Brevo** (smtp-relay.brevo.com:587); los recordatorios llegan a casillas reales.

✅ **Servicios recurrentes** — segundo tipo de gasto compartido, sin fecha fin. `ServiceController` (CRUD + `cancelar()`), `app/Services/ServiceChargeGenerator.php` genera el primer cargo al crear el servicio. El comando `php artisan servicios:generar-cargos {--dias-anticipacion=5}` genera el cargo del próximo mes cuando se acerca su vencimiento (idempotente, corre solo vía scheduler `dailyAt('07:00')`, una hora antes de los recordatorios). Reparto por % fijo mientras el servicio esté activo; cancelar detiene la generación futura pero no toca los cargos pendientes. **Monto mensual editable** desde "Editar servicio" (ej. aumento de tarifa): como cada `ServiceCharge` guarda su propio `monto` al generarse, cambiar `monto_mensual` no toca los cargos ya generados, solo los que se creen después — no hizo falta columna ni tabla de historial nueva. Integrado en Seguimiento, Dashboard, vista de invitado y recordatorios por email.

✅ **Link de invitado por token** — ruta pública `GET /p/{participant:token}` → vista solo-lectura con las cuotas y cargos del participante. Botón "Regenerar link" en edit.

✅ **Titular como participante** — columna `participants.es_titular` (con backfill). Al registrarse, `User::crearParticipanteTitular` crea automáticamente el participante del dueño; aparece preseleccionado en el reparto al crear una compra, con badge "Vos" en el listado, y no se puede eliminar.

✅ **Registro público desactivado** — rutas `register` comentadas en `routes/auth.php` (`/register` da 404). App de uso personal, altas de usuario se hacen manualmente. `RegistrationTest` verifica que quede deshabilitado.

**Tests:** 47 tests OK en toda la suite (12 en `GastosCompartidosFlowTest.php`, 10 en `ServiciosFlowTest.php`, resto en Auth/Profile/Example). `phpunit.xml` usa sqlite `:memory:`. Correr: `php artisan test`.

## Cómo correr (local)
```
php artisan serve      # http://127.0.0.1:8000
npm run dev            # Vite (5173)
php artisan recordatorios:enviar --todas          # probar emails (Mailtrap captura todo)
php artisan servicios:generar-cargos --dias-anticipacion=5   # probar generación de cargos
```
⚠️ Tras editar `.env`: `php artisan config:clear`. Para que el link de invitado tenga el host correcto en local, setear `APP_URL=http://127.0.0.1:8000`.

## Deploy (Hostinger SSH + GitHub Actions)
- `.github/workflows/deploy.yml` (copiado del proyecto `agenda`): job tests → job deploy (npm build en CI + ssh/scp con reintentos). SSH puerto **65002**.
- App en `/home/u540878453/domains/<dominio>/gastos-compartidos`; docroot `public_html` con `index.php` shim. `public/build` gitignored → se compila en CI y se sincroniza.
- Secrets GitHub: SSH_HOST/SSH_PORT(65002)/SSH_USERNAME(u540878453)/SSH_KEY (mismos que agenda) + REMOTE_APP + REMOTE_BUILD.
- Cron Hostinger: `php artisan schedule:run` cada minuto.

## Pendiente / mejoras opcionales
- Confirmar que el cron quedó en `* * * * *` (cada minuto) en el hosting real. El comando nuevo `servicios:generar-cargos` NO necesita un cron aparte: ya corre dentro del mismo `schedule:run` que dispara los recordatorios.
- Bajar `--pausa` del scheduler de 15 a 1-2 (Brevo no tiene el límite agresivo de Mailtrap).
- (Futuro) Notificaciones por WhatsApp (Twilio) — descartado por costo por ahora.
- (Futuro) Reactivar un servicio cancelado — hoy no existe; si cambian las condiciones hay que cargar uno nuevo (mismo criterio que ya existe para compras: "para cambiar el reparto, eliminá y recargá").
