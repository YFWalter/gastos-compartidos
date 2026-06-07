<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar participante') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @include('participants.partials.form', [
                    'participant' => $participant,
                    'action' => route('participants.update', $participant),
                    'method' => 'PATCH',
                ])
            </div>

            {{-- Link de invitado --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg" x-data="{ copiado: false }">
                <h3 class="font-semibold text-gray-800">Link de invitado</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Compartí este link con {{ $participant->nombre }} para que vea sus cuotas sin necesidad de registrarse.
                </p>

                <div class="mt-3 flex items-center gap-2">
                    <input type="text" readonly x-ref="link" value="{{ $participant->enlace_invitado }}"
                        class="flex-1 bg-gray-50 border-gray-300 rounded-md shadow-sm text-sm text-gray-700" />
                    <button type="button"
                        @click="navigator.clipboard.writeText($refs.link.value); copiado = true; setTimeout(() => copiado = false, 2000)"
                        class="shrink-0 inline-flex items-center px-3 py-2 bg-gray-800 text-white text-xs font-semibold rounded-md hover:bg-gray-700">
                        <span x-show="!copiado">Copiar</span>
                        <span x-show="copiado" x-cloak>¡Copiado!</span>
                    </button>
                    <a href="{{ $participant->enlace_invitado }}" target="_blank"
                        class="shrink-0 text-sm text-indigo-600 hover:text-indigo-900 underline">Abrir</a>
                </div>

                <form method="POST" action="{{ route('participants.token', $participant) }}" class="mt-4"
                    onsubmit="return confirm('¿Generar un link nuevo? El link anterior dejará de funcionar.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-900 underline">
                        Regenerar link (invalida el anterior)
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
