<x-mail::message>
# Hola {{ $nombre }} 👋

Te recordamos que tenés las siguientes cuotas y cargos por pagar:

<x-mail::table>
| Compra/Servicio | Detalle | Vence | Monto |
|:-----------------|:-------:|:-----:|------:|
@foreach ($items as $item)
| {{ $item['descripcion'] }} | {{ $item['detalle'] }} | {{ $item['vencimiento']->format('d/m/Y') }} | $ {{ number_format($item['monto'], 2, ',', '.') }} |
@endforeach
</x-mail::table>

**Total a pagar: $ {{ number_format($total, 2, ',', '.') }}**

@if ($enlace)
<x-mail::button :url="$enlace">
Ver mis cuotas y cargos
</x-mail::button>
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
