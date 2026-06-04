<x-mail::message>
# Hola {{ $nombre }} 👋

Estas son las cuotas próximas a vencer de tus compras y lo que adeuda cada participante:

@foreach ($cuotas as $cuota)
**{{ $cuota['descripcion'] }}** — cuota {{ $cuota['numero'] }}/{{ $cuota['cantidad'] }} · vence {{ $cuota['vencimiento']->format('d/m/Y') }}

<x-mail::table>
| Participante | Monto pendiente |
|:-------------|----------------:|
@foreach ($cuota['detalle'] as $d)
| {{ $d['nombre'] }} | $ {{ number_format($d['monto'], 2, ',', '.') }} |
@endforeach
| **Subtotal cuota** | **$ {{ number_format($cuota['pendiente'], 2, ',', '.') }}** |
</x-mail::table>

@endforeach

**Total pendiente: $ {{ number_format($total, 2, ',', '.') }}**

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
