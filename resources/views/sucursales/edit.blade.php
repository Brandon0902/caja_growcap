{{-- resources/views/sucursales/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Editar Sucursal') }}
        </h2>
    </x-slot>

    <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        {{-- Muestra errores de validación --}}
        <x-validation-errors :errors="$errors" class="mb-4" />

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form action="{{ route('sucursales.update', $sucursal) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Nombre --}}
                <div class="mb-4">
                    <x-label name="nombre" value="Nombre" />
                    <x-input
                        name="nombre"
                        type="text"
                        :value="old('nombre', $sucursal->nombre)"
                        required
                        autofocus
                    />
                </div>

                {{-- Dirección --}}
                <div class="mb-4">
                    <x-label name="direccion" value="Dirección" />
                    <x-input
                        name="direccion"
                        type="textarea"
                        :value="old('direccion', $sucursal->direccion)"
                    />
                </div>

                {{-- Teléfono --}}
                <div class="mb-4">
                    <x-label name="telefono" value="Teléfono" />
                    <x-input
                        name="telefono"
                        type="text"
                        :value="old('telefono', $sucursal->telefono)"
                        required
                    />
                </div>

                {{-- Gerente --}}
                <div class="mb-4">
                    <x-label name="gerente_id" value="Gerente" />
                    <x-input name="gerente_id" type="select">
                        <option value="">{{ __('— Seleccionar —') }}</option>
                        @foreach($gerentes as $g)
                            <option
                                value="{{ $g->id_usuario }}"
                                {{ old('gerente_id', $sucursal->gerente_id) == $g->id_usuario ? 'selected' : '' }}
                            >
                                {{ $g->name }} ({{ $g->email }})
                            </option>
                        @endforeach
                    </x-input>
                </div>

                {{-- Política Crediticia --}}
                <div class="mb-4">
                    <x-label name="politica_crediticia" value="Política Crediticia (opcional)" />
                    <x-input
                        name="politica_crediticia"
                        type="textarea"
                        :value="old('politica_crediticia', $sucursal->politica_crediticia)"
                    />
                </div>

                {{-- Acceso Activo --}}
                <div class="mb-6 flex items-center">
                    <x-checkbox
                        name="acceso_activo"
                        :checked="old('acceso_activo', $sucursal->acceso_activo)"
                    />
                    <x-label name="acceso_activo" value="Acceso Activo" class="ml-2" />
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
                    <x-button
                        type="submit"
                        class="bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600"
                    >
                        {{ __('Actualizar') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>