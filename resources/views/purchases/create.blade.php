<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nueva compra') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @if ($participants->isEmpty())
                    <div class="text-center text-gray-600">
                        <p class="mb-4">Primero necesitás cargar al menos un participante para poder repartir la compra.</p>
                        <a href="{{ route('participants.create') }}">
                            <x-primary-button>{{ __('Cargar un participante') }}</x-primary-button>
                        </a>
                    </div>
                @else
                    <form method="POST" action="{{ route('purchases.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="descripcion" :value="__('Descripción')" />
                            <x-text-input id="descripcion" name="descripcion" type="text" class="mt-1 block w-full"
                                :value="old('descripcion')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('descripcion')" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="monto_total" :value="__('Monto total')" />
                                <x-text-input id="monto_total" name="monto_total" type="number" step="0.01" min="0.01"
                                    class="mt-1 block w-full" :value="old('monto_total')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('monto_total')" />
                            </div>
                            <div>
                                <x-input-label for="cantidad_cuotas" :value="__('Cantidad de cuotas')" />
                                <x-text-input id="cantidad_cuotas" name="cantidad_cuotas" type="number" min="1" max="360"
                                    class="mt-1 block w-full" :value="old('cantidad_cuotas', 1)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('cantidad_cuotas')" />
                            </div>
                            <div>
                                <x-input-label for="fecha_primera_cuota" :value="__('1ª cuota')" />
                                <x-text-input id="fecha_primera_cuota" name="fecha_primera_cuota" type="date"
                                    class="mt-1 block w-full" :value="old('fecha_primera_cuota')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('fecha_primera_cuota')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="notas" :value="__('Notas (opcional)')" />
                            <textarea id="notas" name="notas" rows="2"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notas') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('notas')" />
                        </div>

                        {{-- Reparto entre participantes --}}
                        @php
                            $oldSplits = old('splits', [['participant_id' => '', 'porcentaje' => '']]);
                        @endphp
                        <div x-data="{
                                rows: @js(array_values($oldSplits)),
                                get total() {
                                    return this.rows.reduce((s, r) => s + (parseFloat(r.porcentaje) || 0), 0);
                                },
                                repartirIgual() {
                                    if (this.rows.length === 0) return;
                                    const base = Math.floor((100 / this.rows.length) * 100) / 100;
                                    this.rows.forEach((r, i) => r.porcentaje = base);
                                    // El resto al primero para sumar 100 exacto.
                                    const resto = Math.round((100 - base * this.rows.length) * 100) / 100;
                                    this.rows[0].porcentaje = Math.round((base + resto) * 100) / 100;
                                }
                             }">
                            <div class="flex items-center justify-between">
                                <x-input-label :value="__('Reparto entre participantes')" />
                                <button type="button" @click="repartirIgual()"
                                    class="text-sm text-indigo-600 hover:text-indigo-900">Repartir en partes iguales</button>
                            </div>

                            <div class="mt-2 space-y-2">
                                <template x-for="(row, i) in rows" :key="i">
                                    <div class="flex items-center gap-2">
                                        <select :name="`splits[${i}][participant_id]`" x-model="row.participant_id"
                                            class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                            <option value="">Elegí un participante…</option>
                                            @foreach ($participants as $p)
                                                <option value="{{ $p->id }}">{{ $p->nombre_completo }}</option>
                                            @endforeach
                                        </select>
                                        <div class="relative w-32">
                                            <input type="number" step="0.01" min="0.01" max="100" :name="`splits[${i}][porcentaje]`"
                                                x-model="row.porcentaje" placeholder="%"
                                                class="w-full pr-7 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 text-sm">%</span>
                                        </div>
                                        <button type="button" @click="rows.splice(i, 1)" x-show="rows.length > 1"
                                            class="text-red-500 hover:text-red-700 px-2" title="Quitar">&times;</button>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <button type="button" @click="rows.push({ participant_id: '', porcentaje: '' })"
                                    class="text-sm text-indigo-600 hover:text-indigo-900">+ Agregar participante</button>
                                <div class="text-sm" :class="Math.abs(total - 100) < 0.01 ? 'text-green-600' : 'text-red-600'">
                                    Total: <span x-text="total.toFixed(2)"></span>%
                                </div>
                            </div>

                            <x-input-error class="mt-2" :messages="$errors->get('splits')" />
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <x-primary-button>{{ __('Registrar compra') }}</x-primary-button>
                            <a href="{{ route('purchases.index') }}"
                                class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Cancelar') }}</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
