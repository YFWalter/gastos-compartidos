<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $service->descripcion }}
            </h2>
            <div class="flex items-center gap-3">
                @if ($service->estaActivo())
                    <form method="POST" action="{{ route('services.cancelar', $service) }}"
                        onsubmit="return confirm('¿Cancelar «{{ $service->descripcion }}»? No se van a generar más cargos, pero los pendientes quedan igual.');">
                        @csrf
                        @method('PATCH')
                        <x-secondary-button type="submit">{{ __('Cancelar servicio') }}</x-secondary-button>
                    </form>
                @endif
                <a href="{{ route('services.edit', $service) }}">
                    <x-secondary-button>{{ __('Editar') }}</x-secondary-button>
                </a>
                <a href="{{ route('services.index') }}"
                    class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Volver') }}</a>
            </div>
        </div>
    </x-slot>

    @php
        $shares = $service->charges->flatMap->shares;
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
                        <dt class="text-gray-500">Monto mensual</dt>
                        <dd class="font-semibold text-gray-900">$ {{ number_format($service->monto_mensual, 2, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Estado</dt>
                        <dd>
                            @if ($service->estaActivo())
                                <span class="inline-flex rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-medium">Activo</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-xs font-medium">Cancelado</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">1er vencimiento</dt>
                        <dd class="font-semibold text-gray-900">{{ $service->fecha_primer_vencimiento->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Participantes</dt>
                        <dd class="font-semibold text-gray-900">{{ $service->splits->count() }}</dd>
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
                @if ($service->notas)
                    <div class="mt-4 text-sm">
                        <dt class="text-gray-500">Notas</dt>
                        <dd class="text-gray-800 whitespace-pre-line">{{ $service->notas }}</dd>
                    </div>
                @endif
                <div class="mt-4 text-sm">
                    @if ($service->avisar_participantes)
                        <span class="inline-flex items-center gap-1 rounded-full bg-green-50 text-green-700 px-3 py-1 text-xs">
                            ✓ Aviso por email a participantes activado
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 text-gray-600 px-3 py-1 text-xs">
                            Aviso por email a participantes desactivado (solo vos recibís el resumen)
                        </span>
                    @endif
                </div>
            </div>

            {{-- Reparto --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Reparto</h3>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($service->splits as $split)
                        <li class="inline-flex items-center gap-2 rounded-full bg-indigo-50 text-indigo-800 px-3 py-1 text-sm">
                            {{ $split->participant->nombre_completo }}
                            <span class="font-semibold">{{ rtrim(rtrim(number_format($split->porcentaje, 2, '.', ''), '0'), '.') }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Cargos --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <h3 class="font-semibold text-gray-800 p-6 pb-3">Cargos</h3>
                <p class="px-6 pb-3 text-xs text-gray-500">
                    Tocá el chip de un participante para marcar su parte como pagada/pendiente,
                    o usá el botón del cargo para marcarlo completo.
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
                            @foreach ($service->charges as $charge)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $charge->numero }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $charge->vencimiento->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">$ {{ number_format($charge->monto, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center gap-2">
                                            @if ($charge->estaPagado())
                                                <span class="inline-flex rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-medium">Pagado</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-xs font-medium">Pendiente</span>
                                            @endif
                                            <form method="POST" action="{{ route('service-charges.toggle', $charge) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900 underline">
                                                    {{ $charge->estaPagado() ? 'Marcar pendiente' : 'Marcar pagado' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($charge->shares as $share)
                                                <form method="POST" action="{{ route('service-charge-shares.toggle', $share) }}">
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
