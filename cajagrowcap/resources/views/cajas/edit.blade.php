{{-- resources/views/cajas/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Editar Caja') }}
        </h2>
    </x-slot>

    <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        {{-- Errores de validación --}}
        <x-validation-errors class="mb-4" />

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form action="{{ route('cajas.update', $caja) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Sucursal --}}
                <div class="mb-4">
                    <x-label for="id_sucursal" value="Sucursal" />
                    <select id="id_sucursal"
                            name="id_sucursal"
                            class="mt-1 block w-full border rounded px-3 py-2
                                   bg-white dark:bg-gray-700 dark:text-gray-200
                                   focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">{{ __('— Seleccionar sucursal —') }}</option>
                        @foreach($sucursales as $s)
                            <option value="{{ $s->id_sucursal }}"
                                {{ old('id_sucursal', $caja->id_sucursal) == $s->id_sucursal ? 'selected' : '' }}>
                                {{ $s->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Nombre de la caja --}}
                <div class="mb-4">
                    <x-label for="nombre" value="Nombre de la caja" />
                    <x-input id="nombre"
                             name="nombre"
                             type="text"
                             :value="old('nombre', $caja->nombre)"
                             required
                             autofocus />
                </div>

                {{-- Cobrador --}}
                <div class="mb-4">
                    <x-label for="responsable_id" value="Cobrador" />
                    <select id="responsable_id"
                            name="responsable_id"
                            class="mt-1 block w-full border rounded px-3 py-2
                                   bg-white dark:bg-gray-700 dark:text-gray-200
                                   focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">{{ __('— Seleccionar cobrador —') }}</option>
                        @foreach($usuarios as $u)
                            <option value="{{ $u->id_usuario }}"
                                {{ old('responsable_id', $caja->responsable_id) == $u->id_usuario ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Fecha de apertura --}}
                <div class="mb-4">
                    <x-label for="fecha_apertura" value="Fecha de apertura" />
                    <x-input id="fecha_apertura"
                             name="fecha_apertura"
                             type="datetime-local"
                             :value="old('fecha_apertura', $caja->fecha_apertura->format('Y-m-d\TH:i'))" />
                </div>

                {{-- Saldo inicial --}}
                <div class="mb-4">
                    <x-label for="saldo_inicial" value="Saldo inicial" />
                    <x-input id="saldo_inicial"
                             name="saldo_inicial"
                             type="number"
                             step="0.01"
                             :value="old('saldo_inicial', $caja->saldo_inicial)" />
                </div>

                {{-- Estado oculto (no editable aquí) --}}
                <input type="hidden" name="estado" value="{{ $caja->estado }}" />

                {{-- Acceso activo --}}
                <div class="mb-6 flex items-center">
                    <x-checkbox name="acceso_activo"
                                :checked="old('acceso_activo', $caja->acceso_activo)" />
                    <x-label for="acceso_activo"
                             value="Acceso activo"
                             class="ml-2" />
                </div>

                {{-- Botones --}}
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('cajas.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md
                              text-gray-700 bg-white hover:bg-gray-100 focus:outline-none
                              focus:ring-2 focus:ring-offset-2 focus:ring-gray-300
                              dark:border-gray-600 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-500">
                        {{ __('Cancelar') }}
                    </a>

                    <x-button type="submit"
                              class="bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600">
                        {{ __('Actualizar caja') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
