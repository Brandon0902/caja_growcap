import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// —————————————————————————————————————————————
// Toggle de tema Claro/Oscuro
// —————————————————————————————————————————————

// 1) Al arrancar, aplicamos 'dark' según localStorage o preferencia del sistema:
if (
  localStorage.theme === 'dark' ||
  (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
) {
  document.documentElement.classList.add('dark');
} else {
  document.documentElement.classList.remove('dark');
}

// 2) Referencias a los íconos en la barra de navegación:
const lightIcon = document.getElementById('theme-toggle-light-icon');
const darkIcon  = document.getElementById('theme-toggle-dark-icon');

// Función para mostrar/ocultar íconos:
function updateThemeIcons() {
  if (document.documentElement.classList.contains('dark')) {
    lightIcon.classList.remove('hidden');
    darkIcon.classList.add('hidden');
  } else {
    darkIcon.classList.remove('hidden');
    lightIcon.classList.add('hidden');
  }
}

// 3) Ejecutar al inicio para renderizar el icono correcto:
updateThemeIcons();

// 4) Al hacer clic en el botón, alternamos la clase 'dark' y guardamos la preferencia:
const toggleBtn = document.getElementById('theme-toggle');
toggleBtn.addEventListener('click', () => {
  document.documentElement.classList.toggle('dark');
  if (document.documentElement.classList.contains('dark')) {
    localStorage.theme = 'dark';
  } else {
    localStorage.theme = 'light';
  }
  updateThemeIcons();
});
