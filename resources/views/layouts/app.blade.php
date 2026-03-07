<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sistema Campaña') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        @auth
            <div x-data="{ sidebarOpen: false }" 
                 @keydown.escape="sidebarOpen = false"
                 x-init="$watch('sidebarOpen', value => {
                     if (window.innerWidth < 1024) {
                         document.body.style.overflow = value ? 'hidden' : ''
                     }
                 })">
                <!-- Navigation -->
                <nav class="bg-white border-b border-gray-200 fixed w-full z-30 top-0">
                    <div class="px-3 py-3 lg:px-5 lg:pl-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center justify-start">
                                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 text-gray-600 rounded cursor-pointer hover:text-gray-900 hover:bg-gray-100">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                                <a href="{{ route('dashboard') }}" class="flex ml-2 md:mr-24">
                                    <span class="self-center text-xl font-semibold sm:text-2xl whitespace-nowrap text-blue-600">Sistema Campaña</span>
                                </a>
                            </div>
                        <div class="flex items-center">
                            <div class="flex items-center ml-3">
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300" aria-expanded="false">
                                        <span class="sr-only">Abrir menú usuario</span>
                                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    </button>
                                    <div x-show="open" @click.away="open = false" class="absolute right-0 z-50 my-4 text-base list-none bg-white divide-y divide-gray-100 rounded shadow">
                                        <div class="px-4 py-3" role="none">
                                            <p class="text-sm text-gray-900" role="none">
                                                {{ Auth::user()->name }}
                                            </p>
                                            <p class="text-sm font-medium text-gray-900 truncate" role="none">
                                                {{ Auth::user()->email }}
                                            </p>
                                        </div>
                                        <ul class="py-1" role="none">
                                            <li>
                                                <form method="POST" action="{{ route('logout') }}">
                                                    @csrf
                                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        Cerrar Sesión
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

                <!-- Mobile Overlay -->
                <div x-show="sidebarOpen" 
                     x-transition:enter="transition-opacity ease-linear duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-linear duration-300" 
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="sidebarOpen = false"
                     class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
                     style="display: none;">
                </div>

                <!-- Sidebar -->
                <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
                       class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform bg-white border-r border-gray-200 lg:translate-x-0 lg:z-20"
                       aria-label="Sidebar">
                <div class="h-full px-3 pb-4 overflow-y-auto bg-white">
                    <ul class="space-y-2 font-medium">
                        @if(Auth::user()->esAdmin())
                        <li>
                            <a href="{{ route('dashboard') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                </svg>
                                <span class="ml-3">Dashboard</span>
                            </a>
                        </li>

                        <!-- <li>
                            <a href="{{ route('lider.dashboard') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('lider.dashboard') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                                </svg>
                                <span class="ml-3">Mi Dashboard</span>
                            </a>
                        </li> -->
                        @endif

                        @if(Auth::user()->puedeVerVotantes())
                        <li>
                            <a href="{{ route('votantes.index') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('votantes.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                </svg>
                                <span class="ml-3">Votantes</span>
                            </a>
                        </li>
                        @endif

                        @if(Auth::user()->esAdmin())
                        <li>
                            <a href="{{ route('predicciones') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('predicciones') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                </svg>
                                <span class="ml-3">Predicciones</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('importar') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('importar') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="ml-3">Importar</span>
                            </a>
                        </li>
                        @endif

                        @if(Auth::user()->puedeGestionarViajes())
                        <li>
                            <a href="{{ route('viajes.index') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('viajes.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"></path>
                                </svg>
                                <span class="ml-3">Viajes</span>
                            </a>
                        </li>
                        @endif

                        @if(Auth::user()->puedeGestionarVisitas())
                        <li>
                            <a href="{{ route('visitas.index') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('visitas.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="ml-3">Visitas</span>
                            </a>
                        </li>
                        @endif

                        <li>
                            <a href="{{ route('datos-maestros.index') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('datos-maestros.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                </svg>
                                <span class="ml-3">Datos Maestros</span>
                            </a>
                        </li>

                        @if(Auth::user()->esAdmin())
                        <li>
                            <a href="{{ route('usuarios.index') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('usuarios.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"></path>
                                </svg>
                                <span class="ml-3">Gestión de Usuarios</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('data-cleanup.index') }}" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 {{ request()->routeIs('data-cleanup.*') ? 'bg-blue-50 text-blue-600' : '' }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="ml-3">Limpieza de Datos</span>
                            </a>
                        </li>
                        @endif

                        @if(Auth::user()->esAdmin() && (Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Coordinador')))
                        <li>
                            <a href="#" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="ml-3">Gastos</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
                </aside>

                <!-- Main Content -->
                <div class="p-4 lg:ml-64">
                    <div class="mt-14">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        @else
            <div class="min-h-screen flex items-center justify-center bg-gray-100">
                {{ $slot }}
            </div>
        @endauth
    </div>

    @livewireScripts
    
    <!-- Leaflet CSS & JS (cargar ANTES de scripts personalizados) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    @stack('scripts')
    
    <script>
        // Flash messages auto-hide
        setTimeout(() => {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 3000);
    </script>
</body>
</html>
