{{-- resources/views/layouts/navigation.blade.php --}}
<aside 
    x-data="{ 
      cajasOpen: {{ request()->routeIs('cajas.*') || request()->routeIs('movimientos-caja.*') ? 'true' : 'false' }},
      catOpen:   {{ request()->routeIs('categoria-ingresos.*') || request()->routeIs('subcategoria-ingresos.*') || request()->routeIs('categoria-gastos.*') || request()->routeIs('subcategoria-gastos.*') ? 'true' : 'false' }},
      adminOpen: {{ request()->routeIs('prestamos.*')                                       ? 'true' : 'false' }},
      clientesOpen: {{ request()->routeIs('user_ahorros.*')                                ? 'true' : 'false' }},
      open:      true 
    }"
    :class="open ? 'w-64 overflow-x-hidden' : 'w-16 overflow-x-hidden'"
    class="fixed inset-y-0 left-0 flex flex-col
           bg-gradient-to-b from-purple-800 to-purple-900
           dark:from-purple-900 dark:to-gray-900
           text-white transition-all duration-200 ease-in-out"
>
  {{-- Logo + título --}}
  <div class="h-16 flex items-center justify-center">
    <a href="{{ route('dashboard') }}"
       class="flex items-center space-x-2 overflow-hidden"
       :class="open ? 'justify-start pl-4' : 'justify-center'">
      <img src="{{ asset('images/rombo_blanco.png') }}"
           alt="Logo Growcap"
           class="h-8 w-auto object-contain" />
      <span x-show="open" class="font-semibold text-lg text-white whitespace-nowrap">
        CAJA GROWCAP
      </span>
    </a>
  </div>

  {{-- Navegación --}}
  <nav class="flex flex-col flex-1 px-2 overflow-y-auto overflow-x-hidden divide-y divide-white/20">

    {{-- Dashboard --}}
    <x-nav-link 
        :href="route('dashboard')" 
        :active="request()->routeIs('dashboard')"
        class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/50 transition w-full"
    >
      <svg xmlns="http://www.w3.org/2000/svg"
           class="h-6 w-6 text-white flex-shrink-0"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round"
           viewBox="0 0 24 24">
        <path d="M3 12l9-9 9 9" />
        <path d="M9 21V12h6v9" />
      </svg>
      <span x-show="open" class="ml-3">Dashboard</span>
    </x-nav-link>

    {{-- Sucursales --}}
    <x-nav-link 
        :href="route('sucursales.index')" 
        :active="request()->routeIs('sucursales.*')"
        class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/50 transition w-full"
    >
      <svg xmlns="http://www.w3.org/2000/svg"
           class="h-6 w-6 text-white flex-shrink-0"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round"
           viewBox="0 0 24 24">
        <path d="M3 21V8a2 2 0 012-2h14a2 2 0 012 2v13" />
        <path d="M16 21V12" />
        <path d="M8 21V12" />
        <path d="M3 8h18" />
      </svg>
      <span x-show="open" class="ml-3">Sucursales</span>
    </x-nav-link>

    {{-- Cajas --}}
    <div class="mt-1 space-y-1">
      <button
        @click="cajasOpen = !cajasOpen"
        :class="cajasOpen ? 'bg-purple-700/50' : 'hover:bg-purple-700/50'"
        class="!text-white flex items-center w-full px-2 py-2 rounded-md transition"
      >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-6 w-6 text-white flex-shrink-0"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             viewBox="0 0 24 24">
          <path d="M3 7l9-4 9 4v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
          <path d="M3 7l9 4 9-4" />
          <path d="M12 11v10" />
        </svg>
        <span x-show="open" class="ml-3 flex-1 text-left">Cajas</span>
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-5 w-5 text-white transform transition-transform"
             :class="cajasOpen ? 'rotate-90' : ''"
             viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd"
                d="M6 6L14 10L6 14V6Z"
                clip-rule="evenodd"/>
        </svg>
      </button>
      <div x-show="cajasOpen" x-cloak class="space-y-1">
        <x-nav-link 
            :href="route('cajas.index')" 
            :active="request()->routeIs('cajas.index')"
            class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Listado</span>
        </x-nav-link>
        <x-nav-link
            :href="route('movimientos-caja.index')"
            :active="request()->routeIs('movimientos-caja.*')"
            class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Movimientos</span>
        </x-nav-link>
      </div>
    </div>

    {{-- Categorías y Subcategorías --}}
    <div class="mt-1 space-y-1">
      <button
        @click="catOpen = !catOpen"
        :class="catOpen ? 'bg-purple-700/50' : 'hover:bg-purple-700/50'"
        class="!text-white flex items-center w-full px-2 py-2 rounded-md transition"
      >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-6 w-6 text-white flex-shrink-0"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             viewBox="0 0 24 24">
          <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <span x-show="open" class="ml-3 flex-1 text-left">Categorías</span>
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-5 w-5 text-white transform transition-transform"
             :class="catOpen ? 'rotate-90' : ''"
             viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd"
                d="M6 6L14 10L6 14V6Z"
                clip-rule="evenodd"/>
        </svg>
      </button>
      <div x-show="catOpen" x-cloak class="space-y-1">
        <x-nav-link
            :href="route('categoria-ingresos.index')"
            :active="request()->routeIs('categoria-ingresos.*')"
            class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Cat. Ingresos</span>
        </x-nav-link>
        <x-nav-link
            :href="route('subcategoria-ingresos.index')"
            :active="request()->routeIs('subcategoria-ingresos.*')"
            class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Subcat. Ingresos</span>
        </x-nav-link>
        <x-nav-link
            :href="route('categoria-gastos.index')"
            :active="request()->routeIs('categoria-gastos.*')"
            class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Cat. Gastos</span>
        </x-nav-link>
        <x-nav-link
            :href="route('subcategoria-gastos.index')"
            :active="request()->routeIs('subcategoria-gastos.*')"
            class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Subcat. Gastos</span>
        </x-nav-link>
      </div>
    </div>

    {{-- Mensajes (menú principal) --}}
    <x-nav-link
        :href="route('mensajes.index')"
        :active="request()->routeIs('mensajes.*')"
        class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/50 transition w-full"
    >
      <svg xmlns="http://www.w3.org/2000/svg"
           class="h-6 w-6 text-white flex-shrink-0"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round"
           viewBox="0 0 24 24">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h12a2 2 0 012 2z" />
      </svg>
      <span x-show="open" class="ml-3">Mensajes</span>
    </x-nav-link>

    {{-- Tickets (menú principal) --}}
    <x-nav-link
        :href="route('tickets.index')"
        :active="request()->routeIs('tickets.*')"
        class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/50 transition w-full"
    >
      <svg xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6 text-white flex-shrink-0"
          fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round"
          viewBox="0 0 24 24">
        <path d="M3 8h18M3 12h18M3 16h18" />
        <circle cx="12" cy="12" r="2" />
      </svg>
      <span x-show="open" class="ml-3">Tickets</span>
    </x-nav-link>

    {{-- Clientes --}}
    <div class="mt-1 space-y-1">
      <button
        @click="clientesOpen = !clientesOpen"
        :class="clientesOpen ? 'bg-purple-700/50' : 'hover:bg-purple-700/50'"
        class="!text-white flex items-center w-full px-2 py-2 rounded-md transition"
      >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-6 w-6 text-white flex-shrink-0"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             viewBox="0 0 24 24">
          <path d="M17 20h5v-2a4 4 0 00-3-3.87" />
          <path d="M9 20H4v-2a4 4 0 013-3.87" />
          <path d="M16 11a4 4 0 10-8 0 4 4 0 008 0z" />
        </svg>
        <span x-show="open" class="ml-3 flex-1 text-left">Clientes</span>
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-5 w-5 text-white transform transition-transform"
             :class="clientesOpen ? 'rotate-90' : ''"
             viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd"
                d="M6 6L14 10L6 14V6Z"
                clip-rule="evenodd"/>
        </svg>
      </button>
      <div x-show="clientesOpen" x-cloak class="space-y-1">
        {{-- Datos de Cliente --}}
        <x-nav-link
          :href="route('user_data.index')"
          :active="request()->routeIs('user_data.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Datos de Cliente</span>
        </x-nav-link>

        {{-- Ahorros --}}
        <x-nav-link
          :href="route('user_ahorros.index')"
          :active="request()->routeIs('user_ahorros.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Ahorros</span>
        </x-nav-link>

         {{-- Inversiones --}}
        <x-nav-link
        :href="route('user_inversiones.index')"
          :active="request()->routeIs('user_inversiones.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Inversiones</span>
        </x-nav-link>

        {{-- Depósitos --}}
        <x-nav-link
          :href="route('depositos.index')"
          :active="request()->routeIs('depositos.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Depósitos</span>
        </x-nav-link>

        {{-- Retiros --}}
        <x-nav-link
          :href="route('retiros.index')"
          :active="request()->routeIs('retiros.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open">Retiros</span>
        </x-nav-link>

        {{-- Préstamos --}}
        <x-nav-link
          :href="route('user_prestamos.index')"
          :active="request()->routeIs('user_prestamos.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Préstamos</span>
        </x-nav-link>

        {{-- Abonos --}}
        <x-nav-link
          :href="route('adminuserabonos.clientes.index')" 
          :active="request()->routeIs('adminuserabonos.clientes.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Abonos</span>
        </x-nav-link>


        {{-- NUEVO: Documentos --}}
        <x-nav-link
          :href="route('documentos.index')"
          :active="request()->routeIs('documentos.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Documentos</span>
        </x-nav-link>
        </div>
      </div>

    {{-- Usuarios administrativos --}}
    <x-nav-link
        :href="route('usuarios.index')"
        :active="request()->routeIs('usuarios.*')"
        class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/50 transition w-full"
    >
      <svg xmlns="http://www.w3.org/2000/svg"
           class="h-6 w-6 text-white flex-shrink-0"
           fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round"
           viewBox="0 0 24 24">
        <path d="M17 20h5v-2a4 4 0 00-3-3.87" />
        <path d="M9 20H4v-2a4 4 0 013-3.87" />
        <path d="M16 11a4 4 0 10-8 0 4 4 0 008 0z" />
      </svg>
      <span x-show="open" class="ml-3">Usuarios</span>
    </x-nav-link>

    {{-- Admin: Préstamos, Inversiones, etc. --}}
    <div class="mt-1 space-y-1">
      <button
        @click="adminOpen = !adminOpen"
        :class="adminOpen ? 'bg-purple-700/50' : 'hover:bg-purple-700/50'"
        class="!text-white flex items-center w-full px-2 py-2 rounded-md transition"
      >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-6 w-6 text-white flex-shrink-0"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"
             viewBox="0 0 24 24">
          <path d="M12 1v2" />
          <path d="M12 21v2" />
          <path d="M4.22 4.22l1.42 1.42" />
          <path d="M18.36 18.36l1.42 1.42" />
          <path d="M1 12h2" />
          <path d="M21 12h2" />
          <path d="M4.22 19.78l1.42-1.42" />
          <path d="M18.36 5.64l1.42-1.42" />
          <circle cx="12" cy="12" r="3" />
        </svg>
        <span x-show="open" class="ml-3 flex-1 text-left">Admin</span>
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-5 w-5 text-white transform transition-transform"
             :class="adminOpen ? 'rotate-90' : ''"
             viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd"
                d="M6 6L14 10L6 14V6Z"
                clip-rule="evenodd"/>
        </svg>
      </button>
      <div x-show="adminOpen" x-cloak class="space-y-1">
        <x-nav-link
          :href="route('clientes.index')"
          :active="request()->routeIs('clientes.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Clientes</span>
        </x-nav-link>
        <x-nav-link
          :href="route('prestamos.index')"
          :active="request()->routeIs('prestamos.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Préstamos</span>
        </x-nav-link>
        <x-nav-link
          :href="route('inversiones.index')"
          :active="request()->routeIs('inversiones.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Inversiones</span>
        </x-nav-link>
        <x-nav-link
          :href="route('ahorros.index')"
          :active="request()->routeIs('ahorros.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Ahorros</span>
        </x-nav-link>
        <x-nav-link
          :href="route('config_mora.index')"
          :active="request()->routeIs('config_mora.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Mora</span>
        </x-nav-link>
        <x-nav-link
          :href="route('empresas.index')"
          :active="request()->routeIs('empresas.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Empresas</span>
        </x-nav-link>
        <x-nav-link
          :href="route('preguntas.index')"
          :active="request()->routeIs('preguntas.*')"
          class="!text-white flex items-center px-2 py-2 rounded-md hover:bg-purple-700/30 transition w-full ml-8"
        >
          <span x-show="open" class="ml-1">Preguntas</span>
        </x-nav-link>
      </div>
    </div>

  </nav>

  {{-- Botón de colapsar --}}
  <div class="p-4">
    <button @click="open = !open"
            class="w-full p-2 bg-purple-700 hover:bg-purple-600 dark:bg-purple-800 dark:hover:bg-purple-700 rounded-full transition">
      <svg :class="{ 'rotate-180': !open }"
           class="h-5 w-5 text-white transform transition"
           xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd"
              d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0
                 111.414 1.414l-3 3a1 1 0
                 01-1.414 0l-3-3a1 1 0
                 010-1.414z"
              clip-rule="evenodd"/>
      </svg>
    </button>
  </div>
</aside>
