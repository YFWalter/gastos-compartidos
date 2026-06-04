@props(['participant' => null, 'action', 'method' => 'POST'])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="nombre" :value="__('Nombre')" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
            :value="old('nombre', $participant?->nombre)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
    </div>

    <div>
        <x-input-label for="apellido" :value="__('Apellido')" />
        <x-text-input id="apellido" name="apellido" type="text" class="mt-1 block w-full"
            :value="old('apellido', $participant?->apellido)" />
        <x-input-error class="mt-2" :messages="$errors->get('apellido')" />
    </div>

    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
            :value="old('email', $participant?->email)" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
        <p class="mt-1 text-sm text-gray-500">Se usará para enviarle los recordatorios de cuotas.</p>
    </div>

    <div>
        <x-input-label for="telefono" :value="__('Teléfono')" />
        <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full"
            :value="old('telefono', $participant?->telefono)" />
        <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ __('Guardar') }}</x-primary-button>
        <a href="{{ route('participants.index') }}"
            class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancelar') }}</a>
    </div>
</form>
