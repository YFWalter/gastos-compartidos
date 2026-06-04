<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $purchase->descripcion }}
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('purchases.edit', $purchase) }}">
                    <x-secondary-button>{{ __('Editar') }}</x-secondary-button>
                </a>
                <a href="{{ route('purchases.index') }}"
                    class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Volver') }}</a>
            </div>
        </div>
    </x-slot>

    @php
        $shares = $purchase->installments->flatMap->shares;
        $totalPagado = $shares->where('estado', 'pagado')->sum('monto');
        $totalPendiente = $shares->where('estado', 'pendiente')->sum('monto');
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Resumen --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Monto total</dt>
                        <dd class="font-semibold text-gray-900">$ {{ number_format($purchase->monto_total, 2, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Cuotas</dt>
                        <dd class="font-semibold text-gray-900">{{ $purchase->cantidad_cuotas }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">1ª cuota</dt>
                        <dd class="font-semibold text-gray-900">{{ $purchase->fecha_primera_cuota->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Participantes</dt>
                        <dd class="font-semibold text-gray-900">{{ $purchase->splits->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Cobrado</dt>
                        <dd class="font-semibold text-green-600">$ {{ number_format($totalPagado, 2, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Pendiente</dt>
                        <dd class="font-semibold text-red-600">$ {{ number_format($totalPendiente, 2, ',', '.') }}</dd>
                    </div>
                </dl>
                @if ($purchase->notas)
                    <div class="mt-4 text-sm">
                        <dt class="text-gray-500">Notas</dt>
                        <dd class="text-gray-800 whitespace-pre-line">{{ $purchase->notas }}</dd>
                    </div>
                @endif
            </div>

            {{-- Reparto --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Reparto</h3>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($purchase->splits as $split)
                        <li class="inline-flex items-center gap-2 rounded-full bg-indigo-50 text-indigo-800 px-3 py-1 text-sm">
                            {{ $split->participant->nombre_completo }}
                            <span class="font-semibold">{{ rtrim(rtrim(number_format($split->porcentaje, 2, '.', ''), '0'), '.') }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Cuotas --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <h3 class="font-semibold text-gray-800 p-6 pb-3">Cuotas</h3>
                <p class="px-6 pb-3 text-xs text-gray-500">
                    Tocá el chip de un participante para marcar su parte como pagada/pendiente,
                    o usá el botón de la cuota para marcarla completa.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N°</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participantes (tocá para marcar)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($purchase->installments as $installment)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $installment->numero }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $installment->vencimiento->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">$ {{ number_format($installment->monto, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center gap-2">
                                            @if ($installment->estaPagada())
                                                <span class="inline-flex rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-medium">Pagada</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-xs font-medium">Pendiente</span>
                                            @endif
                                            <form method="POST" action="{{ route('installments.toggle', $installment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900 underline">
                                                    {{ $installment->estaPagada() ? 'Marcar pendiente' : 'Marcar pagada' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($installment->shares as $share)
                                                <form method="POST" action="{{ route('shares.toggle', $share) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs border transition
                                                            {{ $share->estaPagada()
                                                                ? 'bg-green-50 border-green-200 text-green-800 hover:bg-green-100'
                                                                : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100' }}"
                                                        title="{{ $share->estaPagada() ? 'Pagado — tocá para marcar pendiente' : 'Pendiente — tocá para marcar pagado' }}">
                                                        <span>{{ $share->estaPagada() ? '✓' : '○' }}</span>
                                                        {{ $share->participant->nombre_completo }}:
                                                        <span class="font-medium">$ {{ number_format($share->monto, 2, ',', '.') }}</span>
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
