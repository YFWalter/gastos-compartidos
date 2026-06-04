<x-mail::message>
# Hola {{ $nombre }} 👋

Te recordamos que tenés las siguientes cuotas por pagar:

<x-mail::table>
| Compra | Cuota | Vence | Monto |
|:-------|:-----:|:-----:|------:|
@foreach ($items as $item)
| {{ $item['descripcion'] }} | {{ $item['numero'] }}/{{ $item['cantidad'] }} | {{ $item['vencimiento']->format('d/m/Y') }} | $ {{ number_format($item['monto'], 2, ',', '.') }} |
@endforeach
</x-mail::table>

**Total a pagar: $ {{ number_format($total, 2, ',', '.') }}**

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
