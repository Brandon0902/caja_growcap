{{-- resources/views/sucursales/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Crear Nueva Sucursal') }}
        </h2>
    </x-slot>

    <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        {{-- Errores de validación --}}
        <x-validation-errors class="mb-4" />

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form action="{{ route('sucursales.store') }}" method="POST">
                @csrf

                {{-- Nombre --}}
                <div class="mb-4">
                    <x-label for="nombre" value="Nombre" />
                    <x-input id="nombre" name="nombre" type="text" />
                </div>

                {{-- Dirección --}}
                <div class="mb-4">
                    <x-label for="direccion" value="Dirección" />
                    <x-input id="direccion" name="direccion" type="textarea" />
                </div>

                {{-- Teléfono --}}
                <div class="mb-4">
                    <x-label for="telefono" value="Teléfono" />
                    <x-input id="telefono" name="telefono" type="text" />
                </div>

                {{-- ✅ Responsable (Gerente / Admin) --}}
                <div class="mb-4">
                    <x-label for="gerente_id" value="Responsable (Gerente/Admin)" />
                    <x-input id="gerente_id" name="gerente_id" type="select">
                        <option value="">{{ __('— Seleccionar —') }}</option>

                        @php
                            $ger = $gerentes->where('rol','gerente');
                            $adm = $gerentes->where('rol','admin');
                        @endphp

                        @if($ger->count())
                            <optgroup label="Gerentes">
                                @foreach($ger as $g)
                                    <option
                                        value="{{ $g->id_usuario }}"
                                        {{ old('gerente_id') == $g->id_usuario ? 'selected' : '' }}
                                    >
                                        {{ $g->name }} ({{ $g->email }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif

                        @if($adm->count())
                            <optgroup label="Admins">
                                @foreach($adm as $g)
                                    <option
                                        value="{{ $g->id_usuario }}"
                                        {{ old('gerente_id') == $g->id_usuario ? 'selected' : '' }}
                                    >
                                        {{ $g->name }} ({{ $g->email }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </x-input>
                </div>

                {{-- Política Crediticia --}}
                <div class="mb-4">
                    <x-label for="politica_crediticia" value="Política Crediticia (opcional)" />
                    <x-input id="politica_crediticia" name="politica_crediticia" type="textarea" />
                </div>

                {{-- Acceso Activo --}}
                <div class="mb-6 flex items-center">
                    <x-checkbox id="acceso_activo" name="acceso_activo" :checked="old('acceso_activo', true)" />
                    <x-label for="acceso_activo" value="Acceso Activo" class="ml-2" />
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
                        {{ __('Guardar') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
