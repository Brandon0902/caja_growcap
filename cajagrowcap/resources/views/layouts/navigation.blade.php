{{-- resources/views/layouts/navigation.blade.php --}}
<aside
  data-nav
  x-data="{
    /* --------- Estado (solo para modo expandido) --------- */
    cajasOpen: {{ request()->routeIs('cajas.*') || request()->routeIs('movimientos-caja.*') || request()->routeIs('gastos.*') ? 'true' : 'false' }},
    contabilidadOpen: {{ request()->routeIs('contabilidad.*') || request()->routeIs('presupuestos.*') ? 'true' : 'false' }},
    adminOpen: {{ 
      request()->routeIs('clientes.*')
      || request()->routeIs('prestamos.*')
      || request()->routeIs('inversiones.*')
      || request()->routeIs('ahorros.*')
      || request()->routeIs('config_mora.*')
      || request()->routeIs('empresas.*')
      || request()->routeIs('preguntas.*')
      || request()->routeIs('admin.permisos.*')
      || request()->routeIs('usuarios.*')
      || request()->routeIs('categoria-ingresos.*')
      || request()->routeIs('subcategoria-ingresos.*')
      || request()->routeIs('categoria-gastos.*')
      || request()->routeIs('subcategoria-gastos.*')
      || request()->routeIs('proveedores.*')
      ? 'true' : 'false' 
    }},
    soporteOpen: {{ request()->routeIs('mensajes.*') || request()->routeIs('tickets.*') ? 'true' : 'false' }},
    clientesOpen: {{
      request()->routeIs('user_data.*')
      || request()->routeIs('user_ahorros.*')
      || request()->routeIs('user_inversiones.*')
      || request()->routeIs('depositos.*')
      || request()->routeIs('retiros.*')
      || request()->routeIs('user_prestamos.*')
      || request()->routeIs('adminuserabonos.*')
      || request()->routeIs('documentos.*')
      ? 'true' : 'false'
    }},
    abonosOpen: {{
      request()->routeIs('adminuserabonos.abonos.general')
      || request()->routeIs('adminuserabonos.clientes.*')
      || request()->routeIs('adminuserabonos.prestamos.*')
      || request()->routeIs('adminuserabonos.abonos.*')
      ? 'true' : 'false'
    }},
    catOpen: {{ 
      request()->routeIs('categoria-ingresos.*')
      || request()->routeIs('subcategoria-ingresos.*')
      || request()->routeIs('categoria-gastos.*')
      || request()->routeIs('subcategoria-gastos.*')
      || request()->routeIs('proveedores.*')
      ? 'true' : 'false'
    }},
    /* (Ya no usamos submenu en CxP; mantenemos el flag por limpieza) */
    cuentasPagarOpen: false,

    /* --------- UI sidebar --------- */
    isMobile: window.matchMedia('(max-width: 768px)').matches,
    open: (() => {
      try {
        const stored = localStorage.getItem('growcap.sidebar.open.v1');
        const onMobile = window.matchMedia('(max-width: 768px)').matches;
        return stored !== null ? JSON.parse(stored) : !onMobile;
      } catch (e) { return true; }
    })(),
    expandedWidth: '16rem',
    collapsedWidth: '6rem',
    toggleSidebar(){ this.open = !this.open; },

    /* --------- Flyout ÚNICO (colapsado/móvil) --------- */
    activeFly: null,   // 'cajas' | 'conta' | 'soporte' | 'admin' | 'clientes' | null
    anchorTop: 0,
    flyTop: 0,
    isFlyout(){ return !this.open || this.isMobile },
    clearOpens(){
      this.cajasOpen=this.adminOpen=this.soporteOpen=this.clientesOpen=this.contabilidadOpen=false;
      this.catOpen=this.abonosOpen=this.cuentasPagarOpen=false;
    },
    openFly(name, btnRef){
      if (!this.isFlyout()) return;
      this.activeFly = this.activeFly === name ? null : name;
      this.$nextTick(() => {
        const btn = this.$refs[btnRef];
        if (!btn) return;
        this.anchorTop = btn.getBoundingClientRect().top + window.scrollY;
        this.computeFlyTop();
      });
    },
    computeFlyTop() {
      this.$nextTick(() => {
        const el = this.$refs.fly;
        const fh = el ? el.offsetHeight : 0;
        const m = 8;
        const minTop = window.scrollY + m;
        const maxTop = window.scrollY + window.innerHeight - fh - m;
        this.flyTop = Math.max(minTop, Math.min(this.anchorTop, maxTop));
      });
    },
  }"
  x-init="
    const mq = window.matchMedia('(max-width: 768px)');
    const update = () => { isMobile = mq.matches; if (isFlyout()) { clearOpens(); activeFly=null; } };
    mq.addEventListener ? mq.addEventListener('change', update) : mq.addListener(update);

    window.addEventListener('resize', () => { if (isFlyout()) { clearOpens(); } computeFlyTop(); });
    window.addEventListener('scroll', () => computeFlyTop());

    $watch('open', v => {
      localStorage.setItem('growcap.sidebar.open.v1', JSON.stringify(v));
      if (!v) { clearOpens(); activeFly=null; } else { activeFly=null; }
    });

    if (!open) { clearOpens(); activeFly=null; }

    // Cerrar flyouts al ocultar completamente el menú desde el header
    window.addEventListener('gc:menuHidden', e => { if (e.detail.hidden) { activeFly = null; } });
  "
  x-effect="document.documentElement.style.setProperty('--sidebar-w', open ? expandedWidth : collapsedWidth)"
  :class="open ? 'w-64 overflow-x-hidden' : 'w-24 overflow-x-hidden'"
  class="fixed inset-y-0 left-0 flex flex-col
         bg-gradient-to-b from-purple-800 to-purple-900
         dark:from-purple-900 dark:to-gray-900
         text-white transition-all duration-200 ease-in-out z-40"
>
  {{-- Logo --}}
  <div class="flex items-center px-3" :class="open ? 'h-16' : 'h-20'">
    <a href="{{ route('dashboard') }}" class="flex items-center w-full overflow-hidden" :class="open ? 'justify-start' : 'justify-center'">
      <img x-show="open" x-cloak src="{{ asset('images/logonew.png') }}" alt="Growcap" class="h-9 max-w-[10.5rem] object-contain transition-all duration-200 ease-out" />
      <img x-show="!open" x-cloak src="{{ asset('images/logonew.png') }}" alt="Growcap" class="h-14 w-14 object-contain transition-all duration-200 ease-out" />
      <span class="sr-only">CAJA GROWCAP</span>
    </a>
  </div>

  <nav x-ref="sidebarScroll" class="flex flex-col flex-1 px-2 overflow-y-auto overflow-x-hidden divide-y divide-white/20">
    <style>
      .flyout{
        position:fixed; z-index:70; background:#fff; color:#1f2937;
        border-radius:.5rem; box-shadow:0 10px 25px rgba(0,0,0,.15);
        max-height:calc(100vh - 2rem); overflow-y:auto; -webkit-overflow-scrolling:touch;
        width:min(18rem, calc(100vw - var(--sidebar-w) - .75rem)); padding:.5rem .25rem;
      }
      @media (max-width: 768px){ .flyout{ max-height:80vh; width:min(20rem, calc(100vw - var(--sidebar-w) - .5rem)); } }
      .toplink-base { display:flex; align-items:center; }
      .toplink-closed { flex-direction:column; align-items:center; }
      .caption { display:block; margin-left:.75rem; }
      .caption-closed { margin-left:0; margin-top:.3rem; font-size:.75rem; line-height:1.05; opacity:.95; text-align:center; }

      .menu-list { list-style:none; margin:0; padding:.25rem; }
      .menu-list li + li { margin-top:.25rem; }
      .menu-link { display:block; padding:.5rem .5rem; border-radius:.5rem; color:#4c1d95; }
      .menu-link:hover { background:#f3f4f6; }
      .submenu-toggle { color:#4c1d95 !important; }
      .submenu-white a { color:#4c1d95 !important; }
      .submenu-white a:hover, .submenu-white button:hover { background:#f3f4f6; }

      /* Parche anti-barra fantasma */
      .flyout--closed{ width:0!important; height:0!important; padding:0!important; border:0!important; box-shadow:none!important; background:transparent!important; opacity:0!important; pointer-events:none!important; overflow:hidden!important; }
      .flyout--open{ }
    </style>

    {{-- Dashboard --}}
    @can('dashboard.ver')
    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
      class="!text-white px-2 py-2 rounded-md hover:bg-purple-700/50 transition w-full toplink-base"
      x-bind:class="{ 'toplink-closed': !open }">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9"/><path d="M9 21V12h6v9"/></svg>
      <span x-bind:class="open ? 'caption' : 'caption-closed'">Dashboard</span>
    </x-nav-link>
    @endcan

    {{-- Sucursales --}}
    @can('sucursales.ver')
    <x-nav-link :href="route('sucursales.index')" :active="request()->routeIs('sucursales.*')"
      class="!text-white px-2 py-2 rounded-md hover:bg-purple-700/50 transition w-full toplink-base"
      x-bind:class="{ 'toplink-closed': !open }">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 21V8a2 2 0 012-2h14a2 2 0 012 2v13"/><path d="M16 21V12"/><path d="M8 21V12"/><path d="M3 8h18"/></svg>
      <span x-bind:class="open ? 'caption' : 'caption-closed'">Sucursales</span>
    </x-nav-link>
    @endcan

    {{-- ========== Cajas ========== --}}
    @canany(['cajas.ver','movimientos_caja.ver','transacciones_cajas.ver','gastos.ver'])
    <div class="mt-1 space-y-1">
      <button
        @click=" if (isFlyout()) { openFly('cajas','cajasBtn'); return; } cajasOpen = !cajasOpen; "
        :class="cajasOpen ? 'bg-purple-700/50' : 'hover:bg-purple-700/50'"
        class="!text-white w-full px-2 py-2 rounded-md transition toplink-base"
        x-bind:class="{ 'toplink-closed': !open }" x-ref="cajasBtn">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 7l9-4 9 4v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/><path d="M3 7l9 4 9-4"/><path d="M12 11v10"/></svg>
        <span x-bind:class="open ? 'caption' : 'caption-closed'">Cajas</span>
      </button>

      <div x-show="cajasOpen && open" x-cloak class="space-y-1 rounded-md bg-white text-slate-900 p-2 shadow-sm">
        @can('cajas.ver')             <x-nav-link :href="route('cajas.index')" :active="request()->routeIs('cajas.index')" class="!text-purple-900 px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Listado</x-nav-link>@endcan
        @can('movimientos_caja.ver') <x-nav-link :href="route('movimientos-caja.index')" :active="request()->routeIs('movimientos-caja.*')" class="!text-purple-900 px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Movimientos</x-nav-link>@endcan
        @canany(['transacciones_cajas.ver','gastos.ver'])
        <x-nav-link :href="route('gastos.index')" :active="request()->routeIs('gastos.*')" class="!text-purple-900 px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Transacciones entre Cajas</x-nav-link>
        @endcanany
      </div>
    </div>
    @endcanany

    {{-- ========== Contabilidad Profunda ========== --}}
    @can('contabilidad_profunda.ver')
    <div class="mt-1 space-y-1">
      <button
        @click=" if (isFlyout()) { openFly('conta','contaBtn'); return; } contabilidadOpen = !contabilidadOpen; "
        :class="contabilidadOpen ? 'bg-purple-700/50' : 'hover:bg-purple-700/50'"
        class="!text-white w-full px-2 py-2 rounded-md transition toplink-base"
        x-bind:class="{ 'toplink-closed': !open }" x-ref="contaBtn">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M6 16v-6"/><path d="M12 16v-10"/><path d="M18 16v-4"/></svg>
        <span x-bind:class="open ? 'caption' : 'caption-closed'">Contabilidad Profunda</span>
      </button>

      <div x-show="contabilidadOpen && open" x-cloak class="space-y-1 rounded-md bg-white text-slate-900 p-2 shadow-sm">
        <x-nav-link :href="route('contabilidad.index')" :active="request()->routeIs('contabilidad.index')" class="!text-purple-900 px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Resumen</x-nav-link>
        @can('presupuestos.ver')
        <x-nav-link :href="route('presupuestos.index')" :active="request()->routeIs('presupuestos.*')" class="!text-purple-900 px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Presupuestos</x-nav-link>
        @endcan
      </div>
    </div>
    @endcan

    {{-- ========== Cuentas por Pagar (link directo, sin submenú) ========== --}}
    @can('cuentas_pagar.ver')
    <div class="mt-1 space-y-1">
      <x-nav-link
        :href="route('cuentas-por-pagar.index')"
        :active="request()->routeIs('cuentas-por-pagar.*')"
        class="!text-white px-2 py-2 rounded-md hover:bg-purple-700/50 transition w-full toplink-base"
        x-bind:class="{ 'toplink-closed': !open }"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
          <path d="M1 10h22"/>
        </svg>
        <span x-bind:class="open ? 'caption' : 'caption-closed'">Cuentas por Pagar</span>
      </x-nav-link>
    </div>
    @endcan

    {{-- ========== Clientes ========== --}}
    @can('clientes.ver')
    <div class="mt-1 space-y-1">
      <button
        @click=" if (isFlyout()) { openFly('clientes','clientesBtn'); return; } clientesOpen = !clientesOpen; "
        :class="clientesOpen ? 'bg-purple-700/50' : 'hover:bg-purple-700/50'"
        class="!text-white w-full px-2 py-2 rounded-md transition toplink-base"
        x-bind:class="{ 'toplink-closed': !open }" x-ref="clientesBtn">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 20h5v-2a4 4 0 00-3-3.87"/><path d="M9 20H4v-2a4 4 0 013-3.87"/><path d="M16 11a4 4 0 10-8 0 4 4 0 008 0z"/></svg>
        <span x-bind:class="open ? 'caption' : 'caption-closed'">Clientes</span>
      </button>

      <div x-show="clientesOpen && open" x-cloak class="space-y-1 rounded-md bg-white text-slate-900 p-2 shadow-sm submenu-white">
        @can('user_data.ver')        <x-nav-link :href="route('user_data.index')" :active="request()->routeIs('user_data.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Datos de Cliente</x-nav-link>@endcan
        @can('user_ahorros.ver')     <x-nav-link :href="route('user_ahorros.index')" :active="request()->routeIs('user_ahorros.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Ahorros</x-nav-link>@endcan
        @can('user_inversiones.ver') <x-nav-link :href="route('user_inversiones.index')" :active="request()->routeIs('user_inversiones.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Inversiones</x-nav-link>@endcan
        @can('depositos.ver')        <x-nav-link :href="route('depositos.index')" :active="request()->routeIs('depositos.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Depósitos</x-nav-link>@endcan
        @can('retiros.ver')          <x-nav-link :href="route('retiros.index')" :active="request()->routeIs('retiros.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Retiros</x-nav-link>@endcan
        @can('user_prestamos.ver')   <x-nav-link :href="route('user_prestamos.index')" :active="request()->routeIs('user_prestamos.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Préstamos</x-nav-link>@endcan

        <div class="ml-8">
          <button @click="abonosOpen = !abonosOpen" class="submenu-toggle flex items-center w-full px-2 py-2 rounded-md transition">
            <span class="flex-1 text-left">Abonos</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform" :class="abonosOpen ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 6L14 10L6 14V6Z" clip-rule="evenodd"/></svg>
          </button>
          <div x-show="abonosOpen" x-cloak class="space-y-1 mt-1">
            @can('adminuserabonos.ver')<x-nav-link :href="route('adminuserabonos.abonos.general')" :active="request()->routeIs('adminuserabonos.abonos.general')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">General</x-nav-link>@endcan
            @can('adminuserabonos.ver')<x-nav-link :href="route('adminuserabonos.clientes.index')" :active="request()->routeIs('adminuserabonos.clientes.*') || request()->routeIs('adminuserabonos.prestamos.*') || request()->routeIs('adminuserabonos.abonos.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Por Cliente</x-nav-link>@endcan
          </div>
        </div>

        @can('documentos.ver')<x-nav-link :href="route('documentos.index')" :active="request()->routeIs('documentos.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Documentos</x-nav-link>@endcan
      </div>
    </div>
    @endcan

    {{-- ========== Soporte ========== --}}
    @canany(['mensajes.ver','tickets.ver'])
    <div class="mt-1 space-y-1">
      <button
        @click=" if (isFlyout()) { openFly('soporte','soporteBtn'); return; } soporteOpen = !soporteOpen; "
        :class="soporteOpen ? 'bg-purple-700/50' : 'hover:bg-purple-700/50'"
        class="!text-white w-full px-2 py-2 rounded-md transition toplink-base"
        x-bind:class="{ 'toplink-closed': !open }" x-ref="soporteBtn">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h12a2 2 0 012 2z"/></svg>
        <span x-bind:class="open ? 'caption' : 'caption-closed'">Soporte</span>
      </button>

      <div x-show="soporteOpen && open" x-cloak class="space-y-1 rounded-md bg-white text-slate-900 p-2 shadow-sm submenu-white">
        @can('mensajes.ver')<x-nav-link :href="route('mensajes.index')" :active="request()->routeIs('mensajes.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Mensajes</x-nav-link>@endcan
        @can('tickets.ver')  <x-nav-link :href="route('tickets.index')"  :active="request()->routeIs('tickets.*')"  class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Tickets</x-nav-link>@endcan
      </div>
    </div>
    @endcanany

    {{-- ========== Admin ========== --}}
    @role('admin','web')
    <div class="mt-1 space-y-1">
      <button
        @click=" if (isFlyout()) { openFly('admin','adminBtn'); return; } adminOpen = !adminOpen; "
        :class="adminOpen ? 'bg-purple-700/50' : 'hover:bg-purple-700/50'"
        class="!text-white w-full px-2 py-2 rounded-md transition toplink-base"
        x-bind:class="{ 'toplink-closed': !open }" x-ref="adminBtn">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 1v2"/><path d="M12 21v2"/><path d="M4.22 4.22l1.42 1.42"/><path d="M18.36 18.36l1.42 1.42"/><path d="M1 12h2"/><path d="M21 12h2"/><path d="M4.22 19.78l1.42-1.42"/><path d="M18.36 5.64l1.42-1.42"/><circle cx="12" cy="12" r="3"/></svg>
        <span x-bind:class="open ? 'caption' : 'caption-closed'">Admin</span>
      </button>

      <div x-show="adminOpen && open" x-cloak class="space-y-1 rounded-md bg-white text-slate-900 p-2 shadow-sm submenu-white">
        @can('clientes.ver')    <x-nav-link :href="route('clientes.index')"   :active="request()->routeIs('clientes.*')"   class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Clientes</x-nav-link>@endcan
        @can('prestamos.ver')   <x-nav-link :href="route('prestamos.index')"  :active="request()->routeIs('prestamos.*')"  class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Préstamos</x-nav-link>@endcan
        @can('inversiones.ver') <x-nav-link :href="route('inversiones.index')" :active="request()->routeIs('inversiones.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Inversiones</x-nav-link>@endcan
        @can('ahorros.ver')     <x-nav-link :href="route('ahorros.index')"    :active="request()->routeIs('ahorros.*')"    class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Ahorros</x-nav-link>@endcan
        @can('config_mora.ver') <x-nav-link :href="route('config_mora.index')" :active="request()->routeIs('config_mora.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Mora</x-nav-link>@endcan
        @can('empresas.ver')    <x-nav-link :href="route('empresas.index')"   :active="request()->routeIs('empresas.*')"   class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Empresas</x-nav-link>@endcan
        @can('preguntas.ver')   <x-nav-link :href="route('preguntas.index')"  :active="request()->routeIs('preguntas.*')"  class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Preguntas</x-nav-link>@endcan
        @can('admin.ver')       <x-nav-link :href="route('admin.permisos.index')" :active="request()->routeIs('admin.permisos.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Permisos</x-nav-link>@endcan
        @can('usuarios.ver')    <x-nav-link :href="route('usuarios.index')"      :active="request()->routeIs('usuarios.*')"      class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Usuarios</x-nav-link>@endcan

        @canany(['categorias.ver','categoria_ingresos.ver','subcategoria_ingresos.ver','categoria_gastos.ver','subcategoria_gastos.ver','proveedores.ver'])
        <div class="ml-8">
          <button @click="catOpen = !catOpen" class="submenu-toggle flex items-center w-full px-2 py-2 rounded-md transition">
            <span class="flex-1 text-left">Categorías</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform" :class="catOpen ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 6L14 10L6 14V6Z" clip-rule="evenodd"/></svg>
          </button>
          <div x-show="catOpen" x-cloak class="space-y-1 mt-1">
            @can('categoria_ingresos.ver')    <x-nav-link :href="route('categoria-ingresos.index')"    :active="request()->routeIs('categoria-ingresos.*')"    class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Cat. Ingresos</x-nav-link>@endcan
            @can('subcategoria_ingresos.ver') <x-nav-link :href="route('subcategoria-ingresos.index')" :active="request()->routeIs('subcategoria-ingresos.*')" class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Subcat. Ingresos</x-nav-link>@endcan
            @can('categoria_gastos.ver')      <x-nav-link :href="route('categoria-gastos.index')"      :active="request()->routeIs('categoria-gastos.*')"      class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Cat. Gastos</x-nav-link>@endcan
            @can('subcategoria_gastos.ver')   <x-nav-link :href="route('subcategoria-gastos.index')"   :active="request()->routeIs('subcategoria-gastos.*')"   class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Subcat. Gastos</x-nav-link>@endcan
            @can('proveedores.ver')           <x-nav-link :href="route('proveedores.index')"           :active="request()->routeIs('proveedores.*')"           class="px-2 py-2 rounded-md hover:bg-purple-50 transition w-full ml-8">Proveedores</x-nav-link>@endcan
          </div>
        </div>
        @endcanany
      </div>
    </div>
    @endrole

    {{-- Backdrop para flyout --}}
    <div x-show="isFlyout() && activeFly" @click="activeFly=null" class="fixed inset-0 z-50" style="background: transparent" x-cloak></div>

    {{-- Flyout único (contenido en lista) --}}
    <div
      x-show="isFlyout() && activeFly"
      x-ref="fly"
      class="flyout z-[75]"
      :class="activeFly ? 'flyout--open' : 'flyout--closed'"
      :style="`top:${flyTop}px; left: calc(var(--sidebar-w) + .5rem);`"
      @click.outside="activeFly=null"
      x-cloak
    >
      <div class="submenu-white">
        {{-- CAJAS --}}
        <template x-if="activeFly==='cajas'">
          <ul class="menu-list">
            @can('cajas.ver')             <li><x-nav-link :href="route('cajas.index')" :active="request()->routeIs('cajas.index')" class="menu-link">Listado</x-nav-link></li>@endcan
            @can('movimientos_caja.ver') <li><x-nav-link :href="route('movimientos-caja.index')" :active="request()->routeIs('movimientos-caja.*')" class="menu-link">Movimientos</x-nav-link></li>@endcan
            @canany(['transacciones_cajas.ver','gastos.ver'])
            <li><x-nav-link :href="route('gastos.index')" :active="request()->routeIs('gastos.*')" class="menu-link">Transacciones entre Cajas</x-nav-link></li>
            @endcanany
          </ul>
        </template>

        {{-- CONTABILIDAD --}}
        <template x-if="activeFly==='conta'">
          <ul class="menu-list">
            <li><x-nav-link :href="route('contabilidad.index')" :active="request()->routeIs('contabilidad.index')" class="menu-link">Resumen</x-nav-link></li>
            @can('presupuestos.ver')
            <li><x-nav-link :href="route('presupuestos.index')" :active="request()->routeIs('presupuestos.*')" class="menu-link">Presupuestos</x-nav-link></li>
            @endcan
          </ul>
        </template>

        {{-- CLIENTES --}}
        <template x-if="activeFly==='clientes'">
          <ul class="menu-list">
            @can('user_data.ver')        <li><x-nav-link :href="route('user_data.index')" :active="request()->routeIs('user_data.*')" class="menu-link">Datos de Cliente</x-nav-link></li>@endcan
            @can('user_ahorros.ver')     <li><x-nav-link :href="route('user_ahorros.index')" :active="request()->routeIs('user_ahorros.*')" class="menu-link">Ahorros</x-nav-link></li>@endcan
            @can('user_inversiones.ver') <li><x-nav-link :href="route('user_inversiones.index')" :active="request()->routeIs('user_inversiones.*')" class="menu-link">Inversiones</x-nav-link></li>@endcan
            @can('depositos.ver')        <li><x-nav-link :href="route('depositos.index')" :active="request()->routeIs('depositos.*')" class="menu-link">Depósitos</x-nav-link></li>@endcan
            @can('retiros.ver')          <li><x-nav-link :href="route('retiros.index')" :active="request()->routeIs('retiros.*')" class="menu-link">Retiros</x-nav-link></li>@endcan
            @can('user_prestamos.ver')   <li><x-nav-link :href="route('user_prestamos.index')" :active="request()->routeIs('user_prestamos.*')" class="menu-link">Préstamos</x-nav-link></li>@endcan

            @can('adminuserabonos.ver')
            <li>
              <button @click="abonosOpen = !abonosOpen" class="submenu-toggle w-full text-left px-2 py-2 rounded-md">Abonos</button>
              <ul x-show="abonosOpen" x-cloak class="menu-list ml-2">
                <li><x-nav-link :href="route('adminuserabonos.abonos.general')" :active="request()->routeIs('adminuserabonos.abonos.general')" class="menu-link">General</x-nav-link></li>
                <li><x-nav-link :href="route('adminuserabonos.clientes.index')" :active="request()->routeIs('adminuserabonos.clientes.*') || request()->routeIs('adminuserabonos.prestamos.*') || request()->routeIs('adminuserabonos.abonos.*')" class="menu-link">Por Cliente</x-nav-link></li>
              </ul>
            </li>
            @endcan

            @can('documentos.ver')<li><x-nav-link :href="route('documentos.index')" :active="request()->routeIs('documentos.*')" class="menu-link">Documentos</x-nav-link></li>@endcan
          </ul>
        </template>

        {{-- SOPORTE --}}
        <template x-if="activeFly==='soporte'">
          <ul class="menu-list">
            @can('mensajes.ver')<li><x-nav-link :href="route('mensajes.index')" :active="request()->routeIs('mensajes.*')" class="menu-link">Mensajes</x-nav-link></li>@endcan
            @can('tickets.ver') <li><x-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')" class="menu-link">Tickets</x-nav-link></li>@endcan
          </ul>
        </template>
        
        {{-- ADMIN --}}
        <template x-if="activeFly==='admin'">
          <ul class="menu-list">
            @can('clientes.ver')
              <li><x-nav-link :href="route('clientes.index')" :active="request()->routeIs('clientes.*')" class="menu-link">Clientes</x-nav-link></li>
            @endcan
            @can('prestamos.ver')
              <li><x-nav-link :href="route('prestamos.index')" :active="request()->routeIs('prestamos.*')" class="menu-link">Préstamos</x-nav-link></li>
            @endcan
            @can('inversiones.ver')
              <li><x-nav-link :href="route('inversiones.index')" :active="request()->routeIs('inversiones.*')" class="menu-link">Inversiones</x-nav-link></li>
            @endcan
            @can('ahorros.ver')
              <li><x-nav-link :href="route('ahorros.index')" :active="request()->routeIs('ahorros.*')" class="menu-link">Ahorros</x-nav-link></li>
            @endcan
            @can('config_mora.ver')
              <li><x-nav-link :href="route('config_mora.index')" :active="request()->routeIs('config_mora.*')" class="menu-link">Mora</x-nav-link></li>
            @endcan
            @can('empresas.ver')
              <li><x-nav-link :href="route('empresas.index')" :active="request()->routeIs('empresas.*')" class="menu-link">Empresas</x-nav-link></li>
            @endcan
            @can('preguntas.ver')
              <li><x-nav-link :href="route('preguntas.index')" :active="request()->routeIs('preguntas.*')" class="menu-link">Preguntas</x-nav-link></li>
            @endcan
        
            @can('admin.ver')
              <li><x-nav-link :href="route('admin.permisos.index')" :active="request()->routeIs('admin.permisos.*')" class="menu-link">Permisos</x-nav-link></li>
            @endcan
            @can('usuarios.ver')
              <li><x-nav-link :href="route('usuarios.index')" :active="request()->routeIs('usuarios.*')" class="menu-link">Usuarios</x-nav-link></li>
            @endcan
        
            @canany(['categoria_ingresos.ver','subcategoria_ingresos.ver','categoria_gastos.ver','subcategoria_gastos.ver','proveedores.ver'])
              <li>
                <button @click="catOpen = !catOpen"
                        class="submenu-toggle w-full text-left px-2 py-2 rounded-md">
                  Categorías
                  <span class="float-right" x-text="catOpen ? '▾' : '▸'"></span>
                </button>
        
                <ul x-show="catOpen" x-cloak class="menu-list ml-2">
                  @can('categoria_ingresos.ver')
                    <li><x-nav-link :href="route('categoria-ingresos.index')" :active="request()->routeIs('categoria-ingresos.*')" class="menu-link">Cat. Ingresos</x-nav-link></li>
                  @endcan
                  @can('subcategoria_ingresos.ver')
                    <li><x-nav-link :href="route('subcategoria-ingresos.index')" :active="request()->routeIs('subcategoria-ingresos.*')" class="menu-link">Subcat. Ingresos</x-nav-link></li>
                  @endcan
                  @can('categoria_gastos.ver')
                    <li><x-nav-link :href="route('categoria-gastos.index')" :active="request()->routeIs('categoria-gastos.*')" class="menu-link">Cat. Gastos</x-nav-link></li>
                  @endcan
                  @can('subcategoria_gastos.ver')
                    <li><x-nav-link :href="route('subcategoria-gastos.index')" :active="request()->routeIs('subcategoria-gastos.*')" class="menu-link">Subcat. Gastos</x-nav-link></li>
                  @endcan
                  @can('proveedores.ver')
                    <li><x-nav-link :href="route('proveedores.index')" :active="request()->routeIs('proveedores.*')" class="menu-link">Proveedores</x-nav-link></li>
                  @endcan
                </ul>
              </li>
            @endcanany
          </ul>
        </template>
      </div>
    </div>

    {{-- Botón colapsar --}}
    <div class="p-4 mt-4 md:mt-auto">
      <button @click="toggleSidebar()" class="w-full p-2 bg-purple-700 hover:bg-purple-600 dark:bg-purple-800 dark:hover:bg-purple-700 rounded-full transition">
        <svg :class="{ 'rotate-180': !open }" class="h-5 w-5 text-white transform transition" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </button>
    </div>
  </nav>
</aside>
