<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Seguimiento de cuotas') }}
        </h2>
    </x-slot>

    @php
        $meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                  7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($porMes->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-10 text-center text-gray-500">
                    No hay cuotas para mostrar. Registrá una compra para empezar.
                </div>
            @else
                @foreach ($porMes as $ym => $cuotas)
                    @php
                        [$anio, $mes] = explode('-', $ym);
                        $totalMes = $cuotas->sum('monto');
                        $pendienteMes = $cuotas->flatMap->shares->where('estado', 'pendiente')->sum('monto');
                    @endphp
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="flex items-center justify-between p-5 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-800">{{ $meses[(int) $mes] }} {{ $anio }}</h3>
                            <div class="text-sm text-gray-500">
                                Total: <span class="text-gray-900 font-medium">$ {{ number_format($totalMes, 2, ',', '.') }}</span>
                                @if ($pendienteMes > 0)
                                    · Pendiente: <span class="text-red-600 font-medium">$ {{ number_format($pendienteMes, 2, ',', '.') }}</span>
                                @else
                                    · <span class="text-green-600 font-medium">Todo cobrado</span>
                                @endif
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vence</th>
                                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compra</th>
                                        <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cuota</th>
                                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($cuotas as $cuota)
                                        <tr>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600">{{ $cuota->vencimiento->format('d/m/Y') }}</td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('purchases.show', $cuota->purchase) }}" class="text-indigo-600 hover:text-indigo-900">{{ $cuota->purchase->descripcion }}</a>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600 text-center">{{ $cuota->numero }} / {{ $cuota->purchase->cantidad_cuotas }}</td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-900 text-right">$ {{ number_format($cuota->monto, 2, ',', '.') }}</td>
                                            <td class="px-5 py-4 whitespace-nowrap text-sm">
                                                @if ($cuota->estaPagada())
                                                    <span class="inline-flex rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-medium">Pagada</span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-xs font-medium">Pendiente</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap text-right text-sm">
                                                <form method="POST" action="{{ route('installments.toggle', $cuota) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900">
                                                        {{ $cuota->estaPagada() ? 'Marcar pendiente' : 'Marcar pagada' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
