<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Servicios') }}
            </h2>
            <a href="{{ route('services.create') }}">
                <x-primary-button>{{ __('Nuevo servicio') }}</x-primary-button>
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
                @if ($services->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        <p class="mb-4">Todavía no registraste ningún servicio recurrente.</p>
                        <a href="{{ route('services.create') }}">
                            <x-primary-button>{{ __('Registrar el primero') }}</x-primary-button>
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto mensual</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cargos generados</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($services as $service)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <a href="{{ route('services.show', $service) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $service->descripcion }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                            $ {{ number_format($service->monto_mensual, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                                            {{ $service->charges_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if ($service->estaActivo())
                                                <span class="inline-flex rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-medium">Activo</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-xs font-medium">Cancelado</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('services.show', $service) }}" class="text-indigo-600 hover:text-indigo-900">Ver</a>
                                                <a href="{{ route('services.edit', $service) }}" class="text-gray-600 hover:text-gray-900">Editar</a>
                                                <form method="POST" action="{{ route('services.destroy', $service) }}"
                                                    onsubmit="return confirm('¿Eliminar el servicio «{{ $service->descripcion }}» y todos sus cargos?');">
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

                    @if ($services->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $services->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
