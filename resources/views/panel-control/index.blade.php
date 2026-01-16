<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h2 class="font-semibold text-xl text-white leading-tight">Panel de Control</h2>
    </div>
  </x-slot>

  <style>[x-cloak]{ display: none !important; }</style>

  <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
       x-data="panelControl(@js($defaultUrl))"
       x-init="init()">
    <div class="grid grid-cols-12 gap-6">
      <aside class="col-span-12 md:col-span-3 bg-white dark:bg-gray-900 rounded-xl shadow p-4">
        <div class="text-xs font-semibold text-gray-500 mb-3">SISTEMA</div>
        <div class="space-y-1">
          @can('admin.ver')
            <a href="{{ route('admin.permisos.index', ['panel' => 1]) }}" data-panel-link
               class="block px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
              Roles y Permisos
            </a>
          @endcan
          @can('usuarios.ver')
            <a href="{{ route('usuarios.index', ['panel' => 1]) }}" data-panel-link
               class="block px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
              Usuarios
            </a>
          @endcan
          @can('clientes.ver')
            <a href="{{ route('clientes.index', ['panel' => 1]) }}" data-panel-link
               class="block px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
              Clientes
            </a>
          @endcan
          @can('empresas.ver')
            <a href="{{ route('empresas.index', ['panel' => 1]) }}" data-panel-link
               class="block px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
              Empresas
            </a>
          @endcan
          @can('categoria_ingresos.ver')
            <a href="{{ route('categoria-ingresos.index', ['panel' => 1]) }}" data-panel-link
               class="block px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
              Categorías Ingresos
            </a>
          @endcan
          @can('categoria_gastos.ver')
            <a href="{{ route('categoria-gastos.index', ['panel' => 1]) }}" data-panel-link
               class="block px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
              Categorías Gastos
            </a>
          @endcan
          @can('proveedores.ver')
            <a href="{{ route('proveedores.index', ['panel' => 1]) }}" data-panel-link
               class="block px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
              Proveedores
            </a>
          @endcan
        </div>
      </aside>

      <section class="col-span-12 md:col-span-9">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow">
          <div class="p-4 border-b dark:border-gray-800 flex items-center justify-between">
            <div class="font-semibold text-gray-800 dark:text-gray-200" x-text="title"></div>
            <div x-show="loading" x-cloak class="text-sm text-gray-500">Cargando…</div>
          </div>
          <div class="p-4" x-ref="content"></div>
        </div>
      </section>
    </div>
  </div>

  @push('scripts')
    <script>
      function panelControl(defaultUrl) {
        return {
          loading: false,
          title: 'Panel',
          defaultUrl,
          currentUrl: defaultUrl,
          init() {
            const current = new URL(window.location.href);
            const target = current.searchParams.get('panel_url') || this.defaultUrl;
            this.load(target, true);

            window.addEventListener('popstate', () => {
              const url = new URL(window.location.href);
              const panelUrl = url.searchParams.get('panel_url') || this.defaultUrl;
              this.load(panelUrl, true);
            });

            document.addEventListener('click', (e) => {
              const link = e.target.closest('a[data-panel-link]');
              if (!link) return;
              e.preventDefault();
              this.setUrl(link.href);
              this.load(link.href);
            });

            document.addEventListener('click', (e) => {
              const a = e.target.closest('[data-panel-content] a');
              if (!a) return;
              const href = a.getAttribute('href');
              if (!href || href.startsWith('#') || a.target === '_blank') return;

              const url = new URL(href, window.location.origin);
              const current = new URL(this.currentUrl, window.location.origin);
              if (url.pathname !== current.pathname) {
                return;
              }

              e.preventDefault();
              this.setUrl(url.toString());
              this.load(url.toString());
            });

            document.addEventListener('submit', (e) => {
              const form = e.target.closest('[data-panel-content] form');
              if (!form) return;
              const method = (form.method || 'GET').toUpperCase();
              if (method !== 'GET') return;

              const actionUrl = new URL(form.action || this.currentUrl, window.location.origin);
              const current = new URL(this.currentUrl, window.location.origin);
              if (actionUrl.pathname !== current.pathname) {
                return;
              }

              e.preventDefault();
              const fd = new FormData(form);
              fd.forEach((value, key) => actionUrl.searchParams.set(key, value));
              this.setUrl(actionUrl.toString());
              this.load(actionUrl.toString());
            });
          },
          setUrl(panelUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('panel_url', panelUrl);
            history.pushState({}, '', url.toString());
          },
          async load(url) {
            this.loading = true;
            this.currentUrl = url;
            try {
              const res = await fetch(url, {
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'X-Panel': '1',
                },
              });
              const html = await res.text();
              this.$refs.content.innerHTML = `<div data-panel-content>${html}</div>`;

              const tmp = document.createElement('div');
              tmp.innerHTML = html;
              const titleNode = tmp.querySelector('[data-title]');
              this.title = titleNode ? titleNode.getAttribute('data-title') : 'Panel';

              const scripts = Array.from(tmp.querySelectorAll('script'));
              for (const script of scripts) {
                if (script.src) {
                  if (document.querySelector(`script[src=\"${script.src}\"]`)) {
                    continue;
                  }
                  await new Promise((resolve, reject) => {
                    const newScript = document.createElement('script');
                    newScript.src = script.src;
                    newScript.async = false;
                    newScript.onload = resolve;
                    newScript.onerror = reject;
                    document.body.appendChild(newScript);
                  });
                  continue;
                }

                if (script.textContent.trim()) {
                  const inlineScript = document.createElement('script');
                  inlineScript.textContent = script.textContent;
                  document.body.appendChild(inlineScript);
                  document.body.removeChild(inlineScript);
                }
              }

              if (window.Alpine && Alpine.initTree) {
                Alpine.initTree(this.$refs.content);
              }
            } catch (err) {
              this.$refs.content.innerHTML = '<div class="text-red-600">Error al cargar la sección.</div>';
            } finally {
              this.loading = false;
            }
          },
        };
      }
    </script>
  @endpush
</x-app-layout>
