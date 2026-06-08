<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Participantes') }}
            </h2>
            <a href="{{ route('participants.create') }}">
                <x-primary-button>{{ __('Nuevo participante') }}</x-primary-button>
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
                @if ($participants->isEmpty())
                    <div class="p-10 text-center text-gray-500">
                        <p class="mb-4">Todavía no cargaste ningún participante.</p>
                        <a href="{{ route('participants.create') }}">
                            <x-primary-button>{{ __('Cargar el primero') }}</x-primary-button>
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($participants as $participant)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $participant->nombre_completo }}
                                            @if ($participant->es_titular)
                                                <span class="ms-1 inline-flex rounded-full bg-indigo-100 text-indigo-800 px-2 py-0.5 text-xs font-medium">Vos</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $participant->email ?: '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $participant->telefono ?: '—' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('participants.edit', $participant) }}"
                                                    class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                                @unless ($participant->es_titular)
                                                    <form method="POST" action="{{ route('participants.destroy', $participant) }}"
                                                        onsubmit="return confirm('¿Eliminar a {{ $participant->nombre_completo }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($participants->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $participants->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
