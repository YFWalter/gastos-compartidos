<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar participante') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @include('participants.partials.form', [
                    'participant' => $participant,
                    'action' => route('participants.update', $participant),
                    'method' => 'PATCH',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
