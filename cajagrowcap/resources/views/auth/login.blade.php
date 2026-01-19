{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login – Growcap</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="antialiased">
  {{-- Fondo global --}}
  <div 
    class="flex items-center justify-center min-h-screen px-4 bg-center bg-cover"
    style="background-image: url('{{ asset('images/fondo_inversiones_003.jpg') }}')"
  >
    {{-- Contenedor responsive: columna en móvil, fila en md+ --}}
    <div class="flex flex-col md:flex-row w-full max-w-4xl overflow-hidden rounded-3xl shadow-2xl bg-purple-700/60">
      
      {{-- Panel ilustración: oculto en xs, full-width en sm, half en md+ --}}
      <div class="hidden sm:flex sm:w-full md:w-1/2 items-center justify-center bg-white/10">
        <img
          src="{{ asset('images/login.jpg') }}"
          alt="Ilustración financiera"
          class="w-full h-auto object-cover max-h-64 sm:max-h-96 md:max-h-none"
        >
      </div>

      {{-- Panel de login: full-width en móvil, half en md+ --}}
      <div class="w-full md:w-1/2 bg-white p-6 sm:p-8 md:p-10">
        <img
          src="{{ asset('images/rombo_blanco.png') }}"
          alt="Logo Growcap"
          class="mx-auto h-12 sm:h-16 mb-6"
        >

        <h2 class="text-center text-2xl sm:text-3xl font-bold text-gray-700 mb-6 sm:mb-8">
          Bienvenido
        </h2>

        <x-auth-session-status class="mb-4 sm:mb-6" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
          @csrf

          <div class="mb-4 sm:mb-6">
            <x-text-input
              id="email"
              type="email"
              name="email"
              :value="old('email')"
              required autofocus
              placeholder="Correo electrónico"
              class="w-full border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
          </div>

          <div class="mb-4 sm:mb-6">
            <x-text-input
              id="password"
              type="password"
              name="password"
              required
              placeholder="Contraseña"
              class="w-full border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
          </div>

          <div class="flex items-center mb-6">
            <input
              id="remember_me"
              type="checkbox"
              name="remember"
              class="rounded text-indigo-600 focus:ring-indigo-500"
            />
            <label for="remember_me" class="ml-2 text-sm text-gray-600">
              {{ __('Recuérdame') }}
            </label>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-between">
            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}"
                 class="text-sm text-indigo-600 hover:underline mb-4 sm:mb-0"
              >
                {{ __('¿Olvidaste tu contraseña?') }}
              </a>
            @endif

            <x-primary-button class="w-full sm:w-auto px-6 py-2 rounded-full">
              {{ __('Entrar') }}
            </x-primary-button>
          </div>
        </form>
      </div>

    </div>
  </div>
</body>
</html>
