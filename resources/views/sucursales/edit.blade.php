{{-- resources/views/sucursales/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Editar Sucursal') }}
        </h2>
    </x-slot>

    <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        {{-- Errores de validación --}}
        <x-validation-errors :errors="$errors" class="mb-4" />

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form action="{{ route('sucursales.update', $sucursal) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Nombre --}}
                <div class="mb-4">
                    <x-input-label for="nombre" :value="__('Nombre')" />
                    <x-text-input
                        id="nombre"
                        name="nombre"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('nombre', $sucursal->nombre)"
                        required
                        autofocus
                    />
                    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                </div>

                {{-- Dirección --}}
                <div class="mb-4">
                    <x-input-label for="direccion" :value="__('Dirección')" />
                    <textarea
                        id="direccion"
                        name="direccion"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 
                               dark:bg-gray-900 dark:text-gray-100 focus:border-purple-500 focus:ring-purple-500"
                    >{{ old('direccion', $sucursal->direccion) }}</textarea>
                    <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                </div>

                {{-- Teléfono --}}
                <div class="mb-4">
                    <x-input-label for="telefono" :value="__('Teléfono')" />
                    <x-text-input
                        id="telefono"
                        name="telefono"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('telefono', $sucursal->telefono)"
                        required
                    />
                    <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                </div>

                {{-- Gerente --}}
                <div class="mb-4">
                    <x-input-label for="gerente_id" :value="__('Gerente')" />
                    <select
                        id="gerente_id"
                        name="gerente_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 
                               dark:bg-gray-900 dark:text-gray-100 focus:border-purple-500 focus:ring-purple-500"
                        required
                    >
                        <option value="">{{ __('— Seleccionar —') }}</option>
                        @foreach($gerentes as $g)
                            <option
                                value="{{ $g->id_usuario }}"
                                @selected(old('gerente_id', $sucursal->gerente_id) == $g->id_usuario)
                            >
                                {{ $g->name }} ({{ $g->email }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('gerente_id')" class="mt-2" />
                </div>

                {{-- Política Crediticia --}}
                <div class="mb-4">
                    <x-input-label for="politica_crediticia" :value="__('Política Crediticia (opcional)')" />
                    <textarea
                        id="politica_crediticia"
                        name="politica_crediticia"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 
                               dark:bg-gray-900 dark:text-gray-100 focus:border-purple-500 focus:ring-purple-500"
                    >{{ old('politica_crediticia', $sucursal->politica_crediticia) }}</textarea>
                    <x-input-error :messages="$errors->get('politica_crediticia')" class="mt-2" />
                </div>

                {{-- Acceso Activo --}}
                <div class="mb-6 flex items-center">
                    <input
                        id="acceso_activo"
                        name="acceso_activo"
                        type="checkbox"
                        value="1"
                        class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500"
                        @checked(old('acceso_activo', (int)$sucursal->acceso_activo) == 1)
                    />
                    <label for="acceso_activo" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Acceso Activo') }}
                    </label>
                </div>

                {{-- Botones --}}
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('sucursales.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md
                              text-gray-700 bg-white hover:bg-gray-100 focus:outline-none
                              focus:ring-2 focus:ring-offset-2 focus:ring-gray-300
                              dark:border-gray-600 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-500">
                        {{ __('Cancelar') }}
                    </a>
                    <x-primary-button class="bg-purple-600 hover:bg-purple-700">
                        {{ __('Actualizar') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
