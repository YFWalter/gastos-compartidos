<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar compra') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <p class="mb-6 text-sm text-gray-500">
                    Solo se pueden editar la descripción y las notas. Para cambiar el monto, la cantidad de
                    cuotas o el reparto, eliminá la compra y registrala de nuevo.
                </p>

                <form method="POST" action="{{ route('purchases.update', $purchase) }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label for="descripcion" :value="__('Descripción')" />
                        <x-text-input id="descripcion" name="descripcion" type="text" class="mt-1 block w-full"
                            :value="old('descripcion', $purchase->descripcion)" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('descripcion')" />
                    </div>

                    <div>
                        <x-input-label for="notas" :value="__('Notas (opcional)')" />
                        <textarea id="notas" name="notas" rows="3"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notas', $purchase->notas) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notas')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Guardar') }}</x-primary-button>
                        <a href="{{ route('purchases.show', $purchase) }}"
                            class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancelar') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
