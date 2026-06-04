<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Tarjetas de resumen --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Compras</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['compras'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Participantes</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['participantes'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Pendiente de cobro</div>
                    <div class="mt-1 text-2xl font-semibold text-red-600">$ {{ number_format($stats['pendiente'], 2, ',', '.') }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Cobrado</div>
                    <div class="mt-1 text-2xl font-semibold text-green-600">$ {{ number_format($stats['pagado'], 2, ',', '.') }}</div>
                </div>
            </div>

            {{-- Próximas cuotas --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="flex items-center justify-between p-6 pb-3">
                    <h3 class="font-semibold text-gray-800">Próximas cuotas pendientes</h3>
                    <a href="{{ route('tracking.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Ver seguimiento completo</a>
                </div>
                @if ($proximas->isEmpty())
                    <p class="px-6 pb-6 text-sm text-gray-500">No tenés cuotas pendientes. 🎉</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compra</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($proximas as $cuota)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cuota->vencimiento->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('purchases.show', $cuota->purchase) }}" class="text-indigo-600 hover:text-indigo-900">{{ $cuota->purchase->descripcion }}</a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">{{ $cuota->numero }} / {{ $cuota->purchase->cantidad_cuotas }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">$ {{ number_format($cuota->monto, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
