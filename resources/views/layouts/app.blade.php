{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>{{ config('app.name', 'Laravel') }}</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  <!-- Scripts -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
  <div x-data="{ open: true }" class="flex min-h-screen">
    {{-- Sidebar --}}
    @include('layouts.navigation')

    {{-- Contenido principal: el margin-left cambia según open --}}
    <div
      :class="open ? 'ml-64' : 'ml-16'"
      class="flex-1 flex flex-col transition-all duration-200 ease-in-out"
    >
      {{-- Barra superior: tema + perfil --}}
      <div class="px-4 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto flex justify-end items-center space-x-4">
          {{-- Toggle Claro/Oscuro --}}
          <button id="theme-toggle"
                  class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            <!-- Sol (modo claro) -->
            <svg id="theme-toggle-light-icon" xmlns="http://www.w3.org/2000/svg"
                 class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="5"/>
              <line x1="12" y1="1" x2="12" y2="3"/>
              <line x1="12" y1="21" x2="12" y2="23"/>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
              <line x1="1" y1="12" x2="3" y2="12"/>
              <line x1="21" y1="12" x2="23" y2="12"/>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
            <!-- Luna (modo oscuro) -->
            <svg id="theme-toggle-dark-icon" xmlns="http://www.w3.org/2000/svg"
                 class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
            </svg>
          </button>

          {{-- Dropdown Usuario --}}
          <x-dropdown align="right" width="48">
            <x-slot name="trigger">
              <button
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md
                       text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300
                       focus:outline-none transition ease-in-out duration-150"
              >
                <span>{{ Auth::user()->name }}</span>
                <svg class="ml-1 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293
                           a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4
                           a1 1 0 010-1.414z"
                        clip-rule="evenodd"/>
                </svg>
              </button>
            </x-slot>

            <x-slot name="content">
              <x-dropdown-link :href="route('profile.edit')">
                {{ __('Profile') }}
              </x-dropdown-link>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                                 onclick="event.preventDefault(); this.closest('form').submit();">
                  {{ __('Log Out') }}
                </x-dropdown-link>
              </form>
            </x-slot>
          </x-dropdown>
        </div>
      </div>

      {{-- Encabezado de página (slot) --}}
      @isset($header)
        <header class="bg-white dark:bg-gray-800 shadow">
          <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {{ $header }}
          </div>
        </header>
      @endisset

      {{-- Contenido principal --}}
      <main class="flex-1 overflow-y-auto">
        {{ $slot }}
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>