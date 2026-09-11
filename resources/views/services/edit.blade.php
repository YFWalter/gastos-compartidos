<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar servicio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="mb-6 text-sm text-gray-500">
                    Para cambiar el vencimiento o el reparto, cancelá este servicio y registrá uno nuevo.
                </p>

                <form method="POST" action="{{ route('services.update', $service) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="descripcion" :value="__('Descripción')" />
                        <x-text-input id="descripcion" name="descripcion" type="text" class="mt-1 block w-full"
                            :value="old('descripcion', $service->descripcion)" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('descripcion')" />
                    </div>

                    <div>
                        <x-input-label for="monto_mensual" :value="__('Monto mensual')" />
                        <x-text-input id="monto_mensual" name="monto_mensual" type="number" step="0.01" min="0.01"
                            class="mt-1 block w-full" :value="old('monto_mensual', $service->monto_mensual)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('monto_mensual')" />
                        <p class="mt-1 text-xs text-gray-500">
                            Un cambio acá no toca los cargos ya generados: se aplica recién al próximo cargo que se cree.
                        </p>
                    </div>

                    <div>
                        <x-input-label for="notas" :value="__('Notas (opcional)')" />
                        <textarea id="notas" name="notas" rows="3"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notas', $service->notas) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notas')" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" name="avisar_participantes" value="0" />
                        <input type="checkbox" id="avisar_participantes" name="avisar_participantes" value="1"
                            @checked(old('avisar_participantes', $service->avisar_participantes))
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="avisar_participantes" class="text-sm text-gray-700">
                            {{ __('Avisar por email a los participantes cuando vence un cargo') }}
                        </label>
                    </div>
                    <p class="-mt-4 text-xs text-gray-500">
                        Desactivalo si algún participante no revisa su correo: igual vas a seguir recibiendo vos el resumen.
                    </p>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Guardar') }}</x-primary-button>
                        <a href="{{ route('services.show', $service) }}"
                            class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancelar') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
