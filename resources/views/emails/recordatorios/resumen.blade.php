<x-mail::message>
# Hola {{ $nombre }} 👋

Estas son las cuotas y cargos próximos a vencer de tus compras y servicios, y lo que adeuda cada participante:

@foreach ($cuotas as $cuota)
**{{ $cuota['descripcion'] }}** — {{ $cuota['detalle'] }} · vence {{ $cuota['vencimiento']->format('d/m/Y') }}

<x-mail::table>
| Participante | Monto pendiente |
|:-------------|----------------:|
@foreach ($cuota['detalle_participantes'] as $d)
| {{ $d['nombre'] }} | $ {{ number_format($d['monto'], 2, ',', '.') }} |
@endforeach
| **Subtotal** | **$ {{ number_format($cuota['pendiente'], 2, ',', '.') }}** |
</x-mail::table>

@endforeach

**Total pendiente: $ {{ number_format($total, 2, ',', '.') }}**

@if ($enlace)
<x-mail::button :url="$enlace">
Ir al seguimiento
</x-mail::button>
@endif

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
