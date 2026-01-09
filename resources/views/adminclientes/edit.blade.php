<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-white leading-tight">
      {{ __('Editar Cliente') }}
    </h2>
  </x-slot>

  <div class="py-6 mx-auto max-w-3xl px-4">
    <x-validation-errors class="mb-4"/>

    <div class="bg-white p-6 shadow-sm rounded-lg">
      <form action="{{ route('clientes.update', $cliente) }}" method="POST"
            x-data="passwordUIEdit()">
        @csrf
        @method('PUT')

        {{-- Código Cliente --}}
        <div class="mb-4">
          <x-label for="codigo_cliente" value="Código Cliente" />
          <x-input id="codigo_cliente" name="codigo_cliente" type="text" maxlength="8"
                   :value="old('codigo_cliente', $cliente->codigo_cliente)"/>
        </div>

        {{-- Nombre y Apellido --}}
        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <x-label for="nombre" value="Nombre" />
            <x-input id="nombre" name="nombre" type="text" :value="old('nombre', $cliente->nombre)" required/>
          </div>
          <div>
            <x-label for="apellido" value="Apellido" />
            <x-input id="apellido" name="apellido" type="text" :value="old('apellido', $cliente->apellido)"/>
          </div>
        </div>

        {{-- Email --}}
        <div class="mb-4">
          <x-label for="email" value="Email" />
          <x-input id="email" name="email" type="email" :value="old('email', $cliente->email)"/>
          <p class="mt-2 text-xs text-gray-500">
            Si cambias el email y presionas <b>Enviar correo al cliente</b>, se enviará al <b>nuevo</b> email guardado.
          </p>
        </div>

        {{-- Teléfono --}}
        <div class="mb-4">
          <x-label for="telefono" value="Teléfono" />
          <x-input id="telefono" name="telefono" type="text" :value="old('telefono', $cliente->telefono)"/>
        </div>

        {{-- Usuario (login) --}}
        <div class="mb-4">
          <x-label for="user" value="Usuario (login)" />
          <x-input id="user" name="user" type="text" :value="old('user', $cliente->user)"/>
        </div>

        {{-- Nueva contraseña (con generar/copiar) --}}
        <div class="mb-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Nueva contraseña (opcional)</span>
            <div class="flex items-center gap-2">
              <button type="button" @click="genPass(12)"
                      class="px-2 py-1 rounded-md text-xs bg-indigo-600 text-white hover:bg-indigo-700">
                Generar
              </button>
              <button type="button" @click="copyPass()"
                      class="px-2 py-1 rounded-md text-xs bg-gray-200 hover:bg-gray-300"
                      :class="copied ? 'ring-2 ring-emerald-400/70' : ''">
                <span x-text="copied ? 'Copiado' : 'Copiar'"></span>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative">
              <x-label for="pass" value="Nueva contraseña" />
              <input
                x-ref="passInput"
                :type="show1 ? 'text' : 'password'"
                id="pass"
                name="pass"
                minlength="8"
                x-model="pass"
                placeholder="********"
                class="mt-1 block w-full pr-10 border-gray-300 rounded"
              />
              <button type="button" @click="show1=!show1"
                      class="absolute right-2 top-9 text-gray-500" tabindex="-1">
                👁
              </button>
            </div>

            <div class="relative">
              <x-label for="pass_confirmation" value="Confirmar contraseña" />
              <input
                :type="show2 ? 'text' : 'password'"
                id="pass_confirmation"
                name="pass_confirmation"
                minlength="8"
                x-model="pass2"
                placeholder="Vuelve a escribirla"
                class="mt-1 block w-full pr-10 border-gray-300 rounded"
              />
              <button type="button" @click="show2=!show2"
                      class="absolute right-2 top-9 text-gray-500" tabindex="-1">
                👁
              </button>
            </div>
          </div>

          <p class="mt-2 text-xs text-gray-500">
            Si no escribes contraseña, se mantiene la actual.
          </p>
        </div>

        {{-- Tipo --}}
        <div class="mb-4">
          <x-label for="tipo" value="Tipo" />
          <x-input id="tipo" name="tipo" type="text" :value="old('tipo', $cliente->tipo)"/>
        </div>

        {{-- Fecha & Status --}}
        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <x-label for="fecha" value="Fecha de Registro" />
            <x-input id="fecha" name="fecha" type="date"
                     :value="old('fecha', $cliente->fecha ? $cliente->fecha->format('Y-m-d') : '')"/>
          </div>
          <div>
            <x-label for="status" value="Status" />
            <select id="status" name="status" class="block w-full border rounded px-3 py-2">
              <option value="1" {{ old('status', (int)$cliente->status) === 1 ? 'selected' : '' }}>Activo</option>
              <option value="0" {{ old('status', (int)$cliente->status) === 0 ? 'selected' : '' }}>Inactivo</option>
            </select>
          </div>
        </div>

        {{-- Cambiar sucursal: solo admin / permiso especial --}}
        @if(auth()->user()->hasRole('admin') || auth()->user()->can('clientes.cambiar_sucursal'))
          <div class="mb-4">
            <x-label for="id_sucursal" value="Sucursal" />
            <select id="id_sucursal" name="id_sucursal" class="block w-full border rounded px-3 py-2" required>
              @foreach($sucursales as $s)
                <option value="{{ $s->id_sucursal }}"
                        @selected(old('id_sucursal', $cliente->id_sucursal) == $s->id_sucursal)>
                  {{ $s->nombre }}
                </option>
              @endforeach
            </select>
          </div>
        @endif

        {{-- Botones --}}
        <div class="flex justify-end gap-2">
          <a href="{{ route('clientes.index') }}" class="px-4 py-2 bg-gray-200 rounded">
            {{ __('Cancelar') }}
          </a>

          {{-- Actualizar normal --}}
          <x-button type="submit">{{ __('Actualizar') }}</x-button>

          {{-- ✅ Nuevo: Actualizar + Enviar correo al NUEVO email --}}
          <button
            type="submit"
            name="notify_email_change"
            value="1"
            class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700"
            title="Actualiza al cliente y envía un correo al email nuevo informado del cambio"
          >
            Enviar correo al cliente
          </button>
        </div>
      </form>
    </div>
  </div>

  @push('scripts')
    <script>
      function passwordUIEdit(){
        return {
          show1:false, show2:false,
          pass: @js(old('pass', '')),
          pass2: @js(old('pass_confirmation', '')),
          copied:false,

          genPass(len=12){
            const lower='abcdefghjkmnpqrstuvwxyz';
            const upper='ABCDEFGHJKMNPQRSTUVWXYZ';
            const nums='23456789';
            const sym='!@#$%*?';
            const pick = s => s[Math.floor(Math.random()*s.length)];

            let out=[pick(lower),pick(upper),pick(nums),pick(sym)];
            const all=lower+upper+nums+sym;
            while(out.length<len) out.push(pick(all));
            out=out.sort(()=>Math.random()-0.5);

            const p=out.join('');
            this.pass=p; this.pass2=p; this.copied=false;
            this.$nextTick(()=>this.$refs.passInput?.focus());
          },

          async copyPass(){
            const text=this.pass||'';
            if(!text) return;
            try { await navigator.clipboard.writeText(text); }
            catch(e){
              const ta=document.createElement('textarea');
              ta.value=text; document.body.appendChild(ta);
              ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
            }
            this.copied=true;
            setTimeout(()=>this.copied=false,1200);
          }
        }
      }
    </script>
  @endpush
</x-app-layout>
