@php
    /* Municipios → objeto clave-valor para Alpine */
    $municipios = $municipios->toArray();                  //  [397 => 'Tlajomulco', …]
@endphp

<div
    x-data="municipioSelect()"
    x-init="init()"
    x-cloak
    class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6"
>

    {{-- ---- ESTADO ---- --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Estado
        </label>

        <select name="id_estado"
                x-model="estado"
                @change="loadMunicipios"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                       bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            <option value="">— Selecciona —</option>
            @foreach ($estados as $id => $nombre)
                <option value="{{ $id }}">{{ $nombre }}</option>
            @endforeach
        </select>

        @error('id_estado')
            <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
        @enderror
    </div>

    {{-- ---- MUNICIPIO ---- --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Municipio
        </label>

        <select name="id_municipio"
                x-model="municipio"
                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                       bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            <option value="">— Selecciona —</option>

            <template x-for="(nombre, id) in municipios" :key="id">
                <option :value="id"
                        :selected="id === municipio"
                        x-text="nombre"></option>
            </template>
        </select>

        @error('id_municipio')
            <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
        @enderror
    </div>

</div>

{{-- ---------- RFC ---------- --}}
<div class="sm:col-span-2 mb-6">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
        RFC
    </label>
    <input type="text" name="rfc"
           value="{{ old('rfc', $userData->rfc ?? '') }}"
           class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                  bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"/>
    @error('rfc')
        <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
    @enderror
</div>

{{-- ---------- Dirección · Colonia · CP ---------- --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Dirección
        </label>
        <input type="text" name="direccion"
               value="{{ old('direccion', $userData->direccion ?? '') }}"
               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                      bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"/>
        @error('direccion')
            <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Colonia
        </label>
        <input type="text" name="colonia"
               value="{{ old('colonia', $userData->colonia ?? '') }}"
               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                      bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"/>
        @error('colonia')
            <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            CP
        </label>
        <input type="text" name="cp"
               value="{{ old('cp', $userData->cp ?? '') }}"
               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded
                      bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"/>
        @error('cp')
            <p class="text-red-600 dark:text-red-300 text-sm">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- …otros campos adicionales… --}}

{{-- ---------- Alpine helper ---------- --}}
<script>
function municipioSelect () {
    return {
        /* siempre como string para comparación estricta */
        estado    : String(`{{ old('id_estado', $userData->id_estado) }}`.trim()),
        municipio : String(`{{ old('id_municipio', $userData->id_municipio) }}`.trim()),
        municipios: @json($municipios),

        /* carga municipios cuando cambia el estado */
        async loadMunicipios() {
            if (!this.estado) {
                this.municipios = {};
                this.municipio  = '';
                return;
            }

            try {
                const res = await fetch(`/municipios?estado=${this.estado}`);
                const data = await res.json();
                this.municipios = data;

                /* conserva selección si sigue existiendo */
                if (!this.municipio || !(this.municipio in data)) {
                    this.municipio = '';
                }
            } catch (e) {
                console.error('Error cargando municipios', e);
            }
        },

        /* al iniciar */
        init() {
            if (this.estado && Object.keys(this.municipios).length === 0) {
                this.loadMunicipios();
            }
        }
    };
}
</script>
