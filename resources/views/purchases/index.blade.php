<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Compras') }}
            </h2>
            <a href="{{ route('purchases.create') }}">
                <x-primary-button>{{ __('Nueva compra') }}</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($purchases->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        <p class="mb-4">Todavía no registraste ninguna compra.</p>
                        <a href="{{ route('purchases.create') }}">
                            <x-primary-button>{{ __('Registrar la primera') }}</x-primary-button>
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto total</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cuotas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">1ª cuota</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($purchases as $purchase)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <a href="{{ route('purchases.show', $purchase) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $purchase->descripcion }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                            $ {{ number_format($purchase->monto_total, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                                            {{ $purchase->cantidad_cuotas }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $purchase->fecha_primera_cuota->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('purchases.show', $purchase) }}" class="text-indigo-600 hover:text-indigo-900">Ver</a>
                                                <a href="{{ route('purchases.edit', $purchase) }}" class="text-gray-600 hover:text-gray-900">Editar</a>
                                                <form method="POST" action="{{ route('purchases.destroy', $purchase) }}"
                                                    onsubmit="return confirm('¿Eliminar la compra «{{ $purchase->descripcion }}» y todas sus cuotas?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($purchases->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $purchases->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
