<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis cuotas — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 antialiased">
    <div class="max-w-3xl mx-auto px-4 py-10">
        <header class="mb-6">
            <p class="text-sm text-gray-500">{{ config('app.name') }}</p>
            <h1 class="text-2xl font-semibold">Hola {{ $participant->nombre_completo }} 👋</h1>
            <p class="text-gray-600 mt-1">Este es el detalle de tus cuotas.</p>
        </header>

        {{-- Totales --}}
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white shadow-sm rounded-lg p-5">
                <div class="text-sm text-gray-500">Pendiente de pago</div>
                <div class="mt-1 text-2xl font-semibold text-red-600">$ {{ number_format($totalPendiente, 2, ',', '.') }}</div>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-5">
                <div class="text-sm text-gray-500">Ya pagado</div>
                <div class="mt-1 text-2xl font-semibold text-green-600">$ {{ number_format($totalPagado, 2, ',', '.') }}</div>
            </div>
        </div>

        {{-- Detalle --}}
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <h2 class="font-semibold p-5 pb-3">Tus cuotas</h2>
            @if ($shares->isEmpty())
                <p class="px-5 pb-5 text-sm text-gray-500">Todavía no tenés cuotas asignadas.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compra</th>
                                <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vence</th>
                                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($shares as $share)
                                <tr>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900">{{ $share->installment->purchase->descripcion }}</td>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600 text-center">{{ $share->installment->numero }} / {{ $share->installment->purchase->cantidad_cuotas }}</td>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600">{{ $share->installment->vencimiento->format('d/m/Y') }}</td>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900 text-right">$ {{ number_format($share->monto, 2, ',', '.') }}</td>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm">
                                        @if ($share->estaPagada())
                                            <span class="inline-flex rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-medium">
                                                Pagado{{ $share->fecha_pago ? ' · ' . $share->fecha_pago->format('d/m/Y') : '' }}
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-xs font-medium">Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <p class="text-xs text-gray-400 mt-6 text-center">
            Esta página es solo informativa. Si tenés dudas sobre un pago, contactá a quien te compartió el link.
        </p>
    </div>
</body>
</html>
