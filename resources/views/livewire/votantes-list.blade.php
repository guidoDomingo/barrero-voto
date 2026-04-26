<div>
    <!-- Header -->
    <div class="mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="mb-2 sm:mb-0">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Gestión de Votantes</h1>
                <p class="mt-1 text-sm text-gray-600 hidden sm:block">Administra y consulta la base de datos de votantes</p>
            </div>
            <!-- Indicador de resultados móvil -->
            <div class="sm:hidden text-sm text-gray-500">
                @if($votantes instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $votantes->total() }} resultados
                @endif
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div role="alert" class="mb-4 bg-green-50 border border-green-200 text-green-800 px-3 sm:px-4 py-3 rounded relative text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filters and Actions -->
    <div class="bg-white rounded-lg shadow p-3 sm:p-4 mb-4 sm:mb-6">
        <!-- Mobile search bar always visible -->
        <div class="mb-3 sm:hidden">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar votante..." 
                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <!-- Toggle filters button for mobile -->
        <div class="sm:hidden mb-3">
            <button onclick="toggleFilters()" class="w-full flex items-center justify-between px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700">
                <span>Filtros</span>
                <svg id="filter-arrow" class="w-4 h-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>

        <!-- Filters container -->
        <div id="filters-container" class="hidden sm:block">
            <!-- Desktop search (hidden on mobile) -->
            <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                <div class="lg:col-span-2">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por nombre, CI, teléfono..." 
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Intención -->
                <select wire:model.live="filtroIntencion" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todas las intenciones</option>
                    <option value="A">A - Seguro</option>
                    <option value="B">B - Probable</option>
                    <option value="C">C - Indeciso</option>
                    <option value="D">D - Difícil</option>
                    <option value="E">E - Contrario</option>
                </select>

                <!-- Estado -->
                <select wire:model.live="filtroEstado" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los estados</option>
                    <option value="Nuevo">Nuevo</option>
                    <option value="Contactado">Contactado</option>
                    <option value="Re-contacto">Re-contacto</option>
                    <option value="Comprometido">Comprometido</option>
                    <option value="Crítico">Crítico</option>
                </select>

                <!-- Estado de Voto -->
                <select wire:model.live="filtroEstadoVoto" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los votos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="votado">Ya votó</option>
                </select>
            </div>

            <!-- Mobile filters (2 columns grid) -->
            <div class="grid grid-cols-2 gap-3 mb-4 sm:hidden">
                <select wire:model.live="filtroIntencion" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Intenciones</option>
                    <option value="A">A - Seguro</option>
                    <option value="B">B - Probable</option>
                    <option value="C">C - Indeciso</option>
                    <option value="D">D - Difícil</option>
                    <option value="E">E - Contrario</option>
                </select>

                <select wire:model.live="filtroEstado" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Estados</option>
                    <option value="Nuevo">Nuevo</option>
                    <option value="Contactado">Contactado</option>
                    <option value="Re-contacto">Re-contacto</option>
                    <option value="Comprometido">Comprometido</option>
                    <option value="Crítico">Crítico</option>
                </select>

                <select wire:model.live="filtroEstadoVoto" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Votos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="votado">Ya votó</option>
                </select>

                <select wire:model.live="filtroPcMovil" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">PC Móvil</option>
                    <option value="paso">Pasó</option>
                    <option value="no_paso">No pasó</option>
                </select>
            </div>

            <!-- Segunda fila de filtros para desktop (TSJE y adicionales) -->
            <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <!-- Distrito -->
                <select wire:model.live="filtroDistrito" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los distritos</option>
                    @foreach($distritos as $distrito)
                        <option value="{{ $distrito }}">{{ $distrito }}</option>
                    @endforeach
                </select>

                <!-- Líder -->
                <select wire:model.live="filtroLider" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos los líderes</option>
                    @foreach($lideres as $lider)
                        <option value="{{ $lider->id }}">{{ $lider->usuario->name }}</option>
                    @endforeach
                </select>

                <!-- PC Móvil -->
                <select wire:model.live="filtroPcMovil" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos (PC Móvil)</option>
                    <option value="paso">Pasó por PC Móvil</option>
                    <option value="no_paso">No pasó por PC Móvil</option>
                </select>

                <!-- Espacio para futuro filtro -->
                <div></div>
            </div>

            <!-- Mobile additional filters -->
            <div class="grid grid-cols-2 gap-3 mb-4 sm:hidden" style="display: none;" id="mobile-extra-filters">
                <select wire:model.live="filtroDistrito" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Distritos</option>
                    @foreach($distritos as $distrito)
                        <option value="{{ $distrito }}">{{ $distrito }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filtroLider" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Líderes</option>
                    @foreach($lideres as $lider)
                        <option value="{{ $lider->id }}">{{ $lider->usuario->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-2 justify-between">
            <!-- Action buttons -->
            <div class="flex flex-col sm:flex-row gap-2">
                <!-- Mobile: Stack buttons vertically -->
                <div class="grid grid-cols-2 gap-2 sm:hidden">
                    @if(!auth()->user()->esVeedor())
                    <a href="{{ route('votantes.create') }}" class="inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        Nuevo
                    </a>
                    @endif

                    <button wire:click="limpiarFiltros" class="inline-flex items-center justify-center px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg text-sm">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                        </svg>
                        Limpiar
                    </button>
                </div>

                @if(!auth()->user()->esVeedor())
                <div class="sm:hidden">
                    <button wire:click="exportarExcel" 
                            wire:loading.attr="disabled"
                            wire:target="exportarExcel"
                            class="w-full inline-flex items-center justify-center px-3 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium rounded-lg transition-colors text-sm">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" wire:loading.remove wire:target="exportarExcel">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        <svg class="animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" wire:loading wire:target="exportarExcel">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 14.293v3.329a1 1 0 01-.293.707l-2 2A1 1 0 010 19.414V12a1 1 0 011-1h3z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportarExcel">Excel</span>
                        <span wire:loading wire:target="exportarExcel">Generando...</span>
                    </button>
                </div>
                @endif

                <!-- Desktop buttons -->
                <div class="hidden sm:flex sm:flex-wrap sm:gap-2">
                    @if(!auth()->user()->esVeedor())
                    <a href="{{ route('votantes.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        Nuevo Votante
                    </a>
                    @endif
                
                    <button wire:click="limpiarFiltros" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                        </svg>
                        Limpiar
                    </button>

                    @if(!auth()->user()->esVeedor())
                    <button wire:click="exportarExcel" 
                            wire:loading.attr="disabled"
                            wire:target="exportarExcel"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" wire:loading.remove wire:target="exportarExcel">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" wire:loading wire:target="exportarExcel">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 14.293v3.329a1 1 0 01-.293.707l-2 2A1 1 0 010 19.414V12a1 1 0 011-1h3z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportarExcel">📊 Exportar Excel</span>
                        <span wire:loading wire:target="exportarExcel">Generando...</span>
                    </button>
                    @endif
                </div>
            </div>
            
            <!-- Pagination controls -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                <label class="text-sm text-gray-600 hidden sm:block">Mostrar:</label>
                <select wire:model.live="perPage" class="border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-full sm:w-auto">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">Todos</option>
                </select>
                <span class="text-sm text-gray-600 hidden sm:block">resultados</span>
            </div>
        </div>
    </div>

    <!-- Table Container - Responsive -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th wire:click="sortBy('ci')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            CI
                        </th>
                        <th wire:click="sortBy('nombres')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            Nombre
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Teléfono
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dirección
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mesa/Orden
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Distrito
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Líder
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Intención
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado de Contacto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado de Voto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            PC Móvil
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($votantes as $votante)
                        <tr class="hover:bg-gray-50 {{ $votante->ya_voto ? 'bg-green-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $votante->ci }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $votante->nombres }} {{ $votante->apellidos }}</div>
                                <div class="flex items-center gap-2">
                                    @if($votante->necesita_transporte)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <svg class="w-2.5 h-2.5 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                                            </svg>
                                            Transporte
                                        </span>
                                    @endif
                                    @if($votante->nro_registro)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" 
                                              title="Número de registro TSJE: {{ $votante->nro_registro }}">
                                            TSJE
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $votante->telefono ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <div class="max-w-xs truncate" title="{{ $votante->direccion }}">
                                    {{ $votante->direccion ?? '-' }}
                                </div>
                                @if($votante->barrio)
                                    <div class="text-xs text-gray-400">{{ $votante->barrio }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                @if($votante->mesa)
                                    <div class="text-sm font-medium">Mesa {{ $votante->mesa }}</div>
                                    @if($votante->orden)
                                        <div class="text-xs text-gray-400">Orden {{ $votante->orden }}</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <div>{{ $votante->distrito ?? '-' }}</div>
                                @if($votante->departamento && $votante->departamento !== $votante->distrito)
                                    <div class="text-xs text-gray-400">{{ $votante->departamento }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                @if($votante->lider && $votante->lider->usuario)
                                    <div>{{ $votante->lider->usuario->name }}</div>
                                    @if($votante->lider->candidato && $votante->lider->candidato->usuario)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 mt-0.5">
                                            {{ $votante->lider->candidato->usuario->name }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400">Sin asignar</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($votante->codigo_intencion === 'A') bg-green-100 text-green-800
                                    @elseif($votante->codigo_intencion === 'B') bg-blue-100 text-blue-800
                                    @elseif($votante->codigo_intencion === 'C') bg-yellow-100 text-yellow-800
                                    @elseif($votante->codigo_intencion === 'D') bg-orange-100 text-orange-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ $votante->codigo_intencion }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $votante->estado_contacto }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($votante->ya_voto)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Ya votó
                                    </span>
                                    @if($votante->voto_registrado_en)
                                        <div class="text-xs text-gray-400 mt-1">
                                            {{ $votante->voto_registrado_en->format('d/m H:i') }}
                                        </div>
                                    @endif
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Pendiente
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($votante->paso_por_pc_movil)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Pasó por PC
                                    </span>
                                    @if($votante->fecha_paso_pc_movil)
                                        <div class="text-xs text-gray-400 mt-1">
                                            {{ $votante->fecha_paso_pc_movil->format('d/m H:i') }}
                                        </div>
                                    @endif
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        No pasó
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @php
                                    $authUser = auth()->user();
                                    $reclamado = $votante->lider_asignado_id && $votante->lider && $votante->lider->candidato_id;
                                    $esPropietario = $authUser->esAdmin() ||
                                        ($authUser->esCandidato() && $authUser->candidato &&
                                         $reclamado && $votante->lider->candidato_id === $authUser->candidato->id) ||
                                        ($authUser->esLider() && $authUser->lider &&
                                         $votante->lider_asignado_id === $authUser->lider->id) ||
                                        (($authUser->esVeedor() || $authUser->esPcMovil()) && $authUser->candidato_id &&
                                         $reclamado && $votante->lider->candidato_id === $authUser->candidato_id);
                                    $puedeLiberar = $reclamado && ($authUser->esAdmin() ||
                                        ($authUser->esCandidato() && $authUser->candidato &&
                                         $votante->lider->candidato_id === $authUser->candidato->id));
                                    $puedeEditar = !$reclamado || $esPropietario;
                                    // Veedor y PC móvil solo interactúan con votantes de su candidato
                                    $puedeInteractuar = $candidatoLiderIds === null ||
                                        ($votante->lider_asignado_id && $candidatoLiderIds->contains($votante->lider_asignado_id));
                                @endphp
                                <div class="flex gap-2 items-center">
                                    @if($reclamado && !$esPropietario)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700" title="Asignado a otro candidato">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Ocupado
                                        </span>
                                    @elseif(!$puedeInteractuar)
                                        <span class="text-xs text-gray-400" title="Votante de otro candidato">—</span>
                                    @else
                                        @if(!$votante->ya_voto && $authUser->puedeMarcarVotos())
                                            <button wire:click="marcarVoto({{ $votante->id }})"
                                                    wire:confirm="¿Confirmar que este votante ya votó?"
                                                    class="text-green-600 hover:text-green-900" title="Marcar voto">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        @endif

                                        @if($authUser->puedeUsarPcMovil() && !$authUser->esVeedor())
                                            <button wire:click="marcarPcMovil({{ $votante->id }})"
                                                    wire:confirm="¿Cambiar el estado de PC móvil para este votante?"
                                                    class="text-blue-600 hover:text-blue-900"
                                                    title="{{ $votante->paso_por_pc_movil ? 'Marcar como NO pasó por PC móvil' : 'Marcar como pasó por PC móvil' }}">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9 12a1 1 0 102 0V8a1 1 0 10-2 0v4zm1-10C4.477 2 0 6.477 0 12s4.477 10 10 10 10-4.477 10-10S15.523 2 10 2zm0 18a8 8 0 110-16 8 8 0 010 16z"></path>
                                                </svg>
                                            </button>
                                        @endif

                                        @if($puedeEditar && ($authUser->puedeCrearVotantes() || $authUser->esAdmin()) && !$authUser->esVeedor())
                                            <a href="{{ route('votantes.edit', $votante->id) }}" class="text-purple-600 hover:text-purple-900" title="Editar">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                                </svg>
                                            </a>
                                        @endif
                                    @endif

                                    @if($puedeLiberar)
                                        <button wire:click="liberarVotante({{ $votante->id }})"
                                                wire:confirm="¿Liberar este votante? Quedará sin líder asignado."
                                                class="text-orange-500 hover:text-orange-700" title="Liberar votante">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-6 py-4 text-center text-sm text-gray-500">
                                No se encontraron votantes
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="sm:hidden">
            @forelse($votantes as $votante)
                <div class="border-b border-gray-200 p-4 {{ $votante->ya_voto ? 'bg-green-50' : 'bg-white' }}">
                    <!-- Header Info -->
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 text-base">
                                {{ $votante->nombres }} {{ $votante->apellidos }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                <span class="font-semibold">CI:</span> {{ $votante->ci }}
                                @if($votante->telefono)
                                    <span class="ml-3">📱 {{ $votante->telefono }}</span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Status indicators principales -->
                        <div class="flex flex-col gap-1 items-end">
                            @if($votante->ya_voto)
                                <span class="px-2 py-1 text-xs font-bold rounded bg-green-600 text-white">
                                    ✓ YA VOTÓ
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-bold rounded bg-red-500 text-white">
                                    ❌ NO VOTÓ
                                </span>
                            @endif
                            
                            @if($votante->paso_por_pc_movil)
                                <span class="px-2 py-1 text-xs font-bold rounded bg-blue-600 text-white">
                                    📱 PASÓ PC
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Intención de Voto Detallada -->
                    <div class="mb-3 p-3 rounded-lg 
                        @if($votante->codigo_intencion === 'A') bg-green-50 border border-green-200
                        @elseif($votante->codigo_intencion === 'B') bg-blue-50 border border-blue-200
                        @elseif($votante->codigo_intencion === 'C') bg-yellow-50 border border-yellow-200
                        @elseif($votante->codigo_intencion === 'D') bg-orange-50 border border-orange-200
                        @else bg-red-50 border border-red-200
                        @endif">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold 
                                    @if($votante->codigo_intencion === 'A') text-green-800
                                    @elseif($votante->codigo_intencion === 'B') text-blue-800
                                    @elseif($votante->codigo_intencion === 'C') text-yellow-800
                                    @elseif($votante->codigo_intencion === 'D') text-orange-800
                                    @else text-red-800
                                    @endif">
                                    🗳️ Intención de Voto: {{ $votante->codigo_intencion }}
                                </div>
                                <div class="text-xs text-gray-600 mt-1">
                                    @if($votante->codigo_intencion === 'A')
                                        Muy probable que vote a favor
                                    @elseif($votante->codigo_intencion === 'B')
                                        Probable que vote a favor
                                    @elseif($votante->codigo_intencion === 'C')
                                        Indeciso / dudoso
                                    @elseif($votante->codigo_intencion === 'D')
                                        Probable que vote en contra
                                    @else
                                        No definido
                                    @endif
                                </div>
                            </div>
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-lg
                                @if($votante->codigo_intencion === 'A') bg-green-500
                                @elseif($votante->codigo_intencion === 'B') bg-blue-500
                                @elseif($votante->codigo_intencion === 'C') bg-yellow-500
                                @elseif($votante->codigo_intencion === 'D') bg-orange-500
                                @else bg-red-500
                                @endif">
                                {{ $votante->codigo_intencion }}
                            </div>
                        </div>
                    </div>

                    <!-- Estado de Compromiso y Contacto -->
                    @if($votante->estado_contacto)
                        <div class="mb-3">
                            <div class="flex items-center justify-between p-2 rounded 
                                @if(in_array(strtolower($votante->estado_contacto), ['comprometido'])) bg-green-100 border border-green-300
                                @elseif(in_array(strtolower($votante->estado_contacto), ['contactado'])) bg-blue-100 border border-blue-300
                                @elseif(in_array(strtolower($votante->estado_contacto), ['no contactado'])) bg-red-100 border border-red-300
                                @else bg-gray-100 border border-gray-300
                                @endif">
                                <span class="text-sm font-semibold text-gray-700">📞 Estado de Compromiso:</span>
                                <span class="px-3 py-1 text-sm font-bold rounded-full 
                                    @if(in_array(strtolower($votante->estado_contacto), ['comprometido'])) bg-green-600 text-white
                                    @elseif(in_array(strtolower($votante->estado_contacto), ['contactado'])) bg-blue-600 text-white
                                    @elseif(in_array(strtolower($votante->estado_contacto), ['no contactado'])) bg-red-600 text-white
                                    @else bg-gray-600 text-white
                                    @endif">
                                    {{ strtoupper($votante->estado_contacto) }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <!-- Información de Ubicación Completa -->
                    <div class="mb-3 bg-gray-50 p-3 rounded-lg">
                        <div class="text-sm font-bold text-gray-700 mb-2">📍 Información de Ubicación</div>
                        <div class="space-y-2 text-sm text-gray-600">
                            @if($votante->direccion)
                                <div class="flex">
                                    <span class="font-medium w-20">Dirección:</span>
                                    <span class="flex-1">{{ $votante->direccion }}</span>
                                </div>
                            @endif
                            @if($votante->distrito)
                                <div class="flex">
                                    <span class="font-medium w-20">Distrito:</span>
                                    <span class="flex-1">{{ $votante->distrito }}</span>
                                </div>
                            @endif
                            @if($votante->departamento && $votante->departamento !== $votante->distrito)
                                <div class="flex">
                                    <span class="font-medium w-20">Depto:</span>
                                    <span class="flex-1">{{ $votante->departamento }}</span>
                                </div>
                            @endif
                            @if($votante->barrio)
                                <div class="flex">
                                    <span class="font-medium w-20">Barrio:</span>
                                    <span class="flex-1">{{ $votante->barrio }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Información Electoral -->
                    @if($votante->mesa || $votante->orden || $votante->nro_registro)
                        <div class="mb-3 bg-blue-50 p-3 rounded-lg">
                            <div class="text-sm font-bold text-gray-700 mb-2">🗪 Datos Electorales</div>
                            <div class="space-y-2 text-sm text-gray-600">
                                @if($votante->mesa)
                                    <div class="flex">
                                        <span class="font-medium w-16">Mesa:</span>
                                        <span class="flex-1 font-bold text-blue-600">{{ $votante->mesa }}</span>
                                    </div>
                                @endif
                                @if($votante->orden)
                                    <div class="flex">
                                        <span class="font-medium w-16">Orden:</span>
                                        <span class="flex-1 font-bold text-blue-600">{{ $votante->orden }}</span>
                                    </div>
                                @endif
                                @if($votante->nro_registro)
                                    <div class="flex">
                                        <span class="font-medium w-16">TSJE:</span>
                                        <span class="flex-1 font-mono text-xs bg-blue-600 text-white px-2 py-1 rounded">{{ $votante->nro_registro }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Líder Asignado -->
                    @php
                        $authUser = auth()->user();
                        $reclamadoM = $votante->lider_asignado_id && $votante->lider && $votante->lider->candidato_id;
                        $esPropietarioM = $authUser->esAdmin() ||
                            ($authUser->esCandidato() && $authUser->candidato &&
                             $reclamadoM && $votante->lider->candidato_id === $authUser->candidato->id) ||
                            ($authUser->esLider() && $authUser->lider &&
                             $votante->lider_asignado_id === $authUser->lider->id) ||
                            (($authUser->esVeedor() || $authUser->esPcMovil()) && $authUser->candidato_id &&
                             $reclamadoM && $votante->lider->candidato_id === $authUser->candidato_id);
                        $puedeLiberarM = $reclamadoM && ($authUser->esAdmin() ||
                            ($authUser->esCandidato() && $authUser->candidato &&
                             $votante->lider->candidato_id === $authUser->candidato->id));
                        $puedeInteractuarM = $candidatoLiderIds === null ||
                            ($votante->lider_asignado_id && $candidatoLiderIds->contains($votante->lider_asignado_id));
                    @endphp
                    @if($votante->lider && $votante->lider->usuario)
                        <div class="mb-3">
                            <div class="flex items-center justify-between p-2 bg-purple-50 border border-purple-200 rounded">
                                <span class="text-sm font-semibold text-purple-700">👥 Líder Asignado:</span>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-purple-800">{{ $votante->lider->usuario->name }}</div>
                                    @if($votante->lider->candidato && $votante->lider->candidato->usuario)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            {{ $votante->lider->candidato->usuario->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if($reclamadoM && !$esPropietarioM)
                                <div class="mt-1 flex items-center gap-1 text-xs text-red-600 font-medium">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                                    Asignado a otro candidato — solo editable por su propietario
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Necesidades de Transporte -->
                    @if($votante->necesita_transporte)
                        <div class="mb-3">
                            <div class="flex items-center justify-between p-2 bg-orange-100 border border-orange-300 rounded">
                                <span class="text-sm font-semibold text-orange-700">🚌 Necesita Transporte:</span>
                                <span class="px-3 py-1 text-sm font-bold bg-orange-600 text-white rounded-full">
                                    {{ $votante->necesita_transporte === 'si' ? 'SÍ' : $votante->necesita_transporte }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <!-- Información de Fechas -->
                    @if($votante->fecha_paso_pc_movil || $votante->updated_at)
                        <div class="mb-3 bg-gray-50 p-3 rounded-lg border-t-2 border-gray-300">
                            <div class="text-xs font-bold text-gray-700 mb-2">🕒 Últimas Actualizaciones</div>
                            <div class="space-y-1 text-xs text-gray-600">
                                @if($votante->fecha_paso_pc_movil)
                                    <div class="flex justify-between">
                                        <span class="font-medium">Paso por PC Móvil:</span>
                                        <span class="font-mono bg-green-100 px-2 py-1 rounded">{{ $votante->fecha_paso_pc_movil->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endif
                                @if($votante->updated_at)
                                    <div class="flex justify-between">
                                        <span class="font-medium">Última Modificación:</span>
                                        <span class="font-mono bg-blue-100 px-2 py-1 rounded">{{ $votante->updated_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Botones de Acción Mejorados -->
                    <div class="mt-4 border-t pt-3 space-y-2">
                        @if($reclamadoM && !$esPropietarioM)
                            <div class="w-full px-4 py-3 text-sm font-bold text-center rounded-lg bg-red-50 border border-red-200 text-red-700">
                                🔒 Votante asignado a otro candidato
                            </div>
                        @elseif(!$puedeInteractuarM)
                            <div class="w-full px-4 py-3 text-sm text-center rounded-lg bg-gray-50 border border-gray-200 text-gray-500">
                                Solo podés actuar sobre votantes de tu candidato
                            </div>
                        @else
                            @if($authUser->puedeUsarPcMovil() && !$authUser->esVeedor())
                                <button wire:click="marcarPcMovil({{ $votante->id }})"
                                        wire:confirm="¿Cambiar el estado de PC móvil para este votante?"
                                        class="w-full px-4 py-3 text-sm font-bold rounded-lg transition-all
                                            @if($votante->paso_por_pc_movil)
                                                bg-green-600 text-white hover:bg-green-700
                                            @else
                                                bg-blue-600 text-white hover:bg-blue-700
                                            @endif">
                                    @if($votante->paso_por_pc_movil)
                                        ✓ Ya pasó por PC Móvil - Marcar NO
                                    @else
                                        📱 Marcar paso por PC Móvil
                                    @endif
                                </button>
                            @endif

                            @if(!$votante->ya_voto && $authUser->puedeMarcarVotos())
                                <button wire:click="marcarVoto({{ $votante->id }})"
                                        wire:confirm="¿Confirmar que este votante ya votó?"
                                        class="w-full px-4 py-3 text-sm font-bold text-white rounded-lg transition-all hover:bg-green-700"
                                        style="background-color: #059669 !important;">
                                    ✓ Marcar que YA VOTÓ
                                </button>
                            @endif

                            @if(($authUser->puedeCrearVotantes() || $authUser->esAdmin()) && !$authUser->esVeedor())
                                <a href="{{ route('votantes.edit', $votante->id) }}"
                                   class="block w-full px-4 py-2 text-white text-center rounded-lg transition-all text-sm font-medium hover:bg-purple-700"
                                   style="background-color: #7c3aed !important;">
                                    ✏️ Editar Información
                                </a>
                            @endif
                        @endif

                        @if($puedeLiberarM)
                            <button wire:click="liberarVotante({{ $votante->id }})"
                                    wire:confirm="¿Liberar este votante? Quedará sin líder asignado."
                                    class="w-full px-4 py-2 text-sm font-bold text-white rounded-lg bg-orange-500 hover:bg-orange-600 transition-all">
                                🔓 Liberar votante
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay votantes</h3>
                    <p class="mt-1 text-sm text-gray-500">No se encontraron votantes con los filtros aplicados.</p>
                </div>
            @endforelse
        </div>

        <!-- Tablet/Web Horizontal View -->
        <div class="hidden sm:block lg:hidden">
            <!-- Header de columnas -->
            <div class="border-b-2 border-gray-300 py-2 bg-gray-50 sticky top-0">
                <div class="grid grid-cols-12 gap-2 items-center text-xs font-semibold text-gray-700 uppercase">
                    <div class="col-span-3">Votante</div>
                    <div class="col-span-2">Ubicación</div>
                    <div class="col-span-1 text-center">Tel</div>
                    <div class="col-span-1 text-center">Int</div>
                    <div class="col-span-1 text-center">Voto</div>
                    <div class="col-span-1 text-center">PC</div>
                    <div class="col-span-1 text-center">Info</div>
                    <div class="col-span-2 text-center">Acciones</div>
                </div>
            </div>
            
            @forelse($votantes as $votante)
                <div class="border-b border-gray-200 py-2 hover:bg-gray-50 {{ $votante->ya_voto ? 'bg-green-50' : 'bg-white' }}">
                    <div class="grid grid-cols-12 gap-2 items-center text-sm">
                        <!-- Columna 1-3: Nombre y CI -->
                        <div class="col-span-3">
                            <div class="font-medium text-gray-900 truncate">{{ $votante->nombres }} {{ $votante->apellidos }}</div>
                            <div class="text-xs text-gray-500">CI: {{ $votante->ci }}</div>
                        </div>
                        
                        <!-- Columna 4-5: Ubicación -->
                        <div class="col-span-2 text-xs text-gray-600">
                            @if($votante->distrito)
                                <div class="truncate" title="{{ $votante->distrito }}">🏢 {{ $votante->distrito }}</div>
                            @endif
                            @if($votante->mesa)
                                <div>🗳️ {{ $votante->mesa }}@if($votante->orden)/{{ $votante->orden }}@endif</div>
                            @endif
                        </div>
                        
                        <!-- Columna 6: Contacto -->
                        <div class="col-span-1 text-center text-xs text-gray-600">
                            @if($votante->telefono)
                                <div class="text-lg">📱</div>
                                <div class="font-mono">{{ substr($votante->telefono, -4) }}</div>
                            @else
                                <div class="text-gray-400">−</div>
                            @endif
                        </div>
                        
                        <!-- Columna 7: Intención -->
                        <div class="col-span-1 text-center">
                            <div class="inline-flex items-center justify-center w-7 h-7 text-sm font-bold rounded-full text-white shadow-sm
                                @if($votante->codigo_intencion === 'A') bg-green-500
                                @elseif($votante->codigo_intencion === 'B') bg-blue-500
                                @elseif($votante->codigo_intencion === 'C') bg-yellow-500
                                @elseif($votante->codigo_intencion === 'D') bg-orange-500
                                @else bg-red-500
                                @endif">
                                {{ $votante->codigo_intencion }}
                            </div>
                        </div>
                        
                        <!-- Columna 8: Estado Voto -->
                        <div class="col-span-1 text-center">
                            @if($votante->ya_voto)
                                <div class="text-green-600">
                                    <div class="text-xl font-bold">✓</div>
                                    <div class="text-xs font-bold">SÍ</div>
                                    @if($votante->voto_registrado_en)
                                        <div class="text-xs text-gray-400">{{ $votante->voto_registrado_en->format('H:i') }}</div>
                                    @endif
                                </div>
                            @else
                                <div class="text-red-500">
                                    <div class="text-xl font-bold">❌</div>
                                    <div class="text-xs font-bold">NO</div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Columna 9: Estado PC -->
                        <div class="col-span-1 text-center">
                            @if($votante->paso_por_pc_movil)
                                <div class="text-blue-600">
                                    <div class="text-xl">📱</div>
                                    <div class="text-xs font-bold">SÍ</div>
                                    @if($votante->fecha_paso_pc_movil)
                                        <div class="text-xs text-gray-400">{{ $votante->fecha_paso_pc_movil->format('H:i') }}</div>
                                    @endif
                                </div>
                            @else
                                <div class="text-orange-500">
                                    <div class="text-xl">🚫</div>
                                    <div class="text-xs font-bold">NO</div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Columna 10: Badges -->
                        <div class="col-span-1 flex flex-col items-center gap-0.5">
                            @if($votante->necesita_transporte)
                                <span class="text-lg" title="Necesita transporte">🚐</span>
                            @endif
                            @if($votante->nro_registro)
                                <span class="text-xs font-bold text-blue-600 px-1 bg-blue-100 rounded" title="Registro TSJE">T</span>
                            @endif
                        </div>
                        
                        <!-- Columna 11-12: Acciones -->
                        <div class="col-span-2 flex justify-center gap-1">
                            @if(!$votante->ya_voto && auth()->user()->puedeMarcarVotos())
                                <button wire:click="marcarVoto({{ $votante->id }})" 
                                        wire:confirm="¿Registrar voto?"
                                        class="px-2 py-1 bg-green-500 text-white rounded text-xs hover:bg-green-600 font-medium transition-colors"
                                        title="Registrar voto">
                                    ✓ Voto
                                </button>
                            @endif

                            @if(auth()->user()->puedeUsarPcMovil())
                                <button wire:click="marcarPcMovil({{ $votante->id }})" 
                                        wire:confirm="{{ $votante->paso_por_pc_movil ? '¿Quitar de PC?' : '¿Registrar en PC?' }}"
                                        class="px-2 py-1 {{ $votante->paso_por_pc_movil ? 'bg-orange-500 hover:bg-orange-600' : 'bg-blue-500 hover:bg-blue-600' }} text-white rounded text-xs font-medium transition-colors"
                                        title="{{ $votante->paso_por_pc_movil ? 'Quitar de PC' : 'Registrar en PC' }}">
                                    {{ $votante->paso_por_pc_movil ? '❌' : '📱' }} PC
                                </button>
                            @endif
                            
                            @if((auth()->user()->puedeCrearVotantes() || auth()->user()->esAdmin()) && !auth()->user()->esVeedor())
                                <a href="{{ route('votantes.edit', $votante->id) }}" 
                                   class="px-2 py-1 bg-purple-500 text-white rounded text-xs hover:bg-purple-600 font-medium transition-colors"
                                   title="Editar votante">
                                    ✏️
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay votantes</h3>
                    <p class="mt-1 text-sm text-gray-500">No se encontraron votantes con los filtros aplicados.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            {{ $votantes->links() }}
        </div>
    </div>

    <script>
        function toggleFilters() {
            const filtersContainer = document.getElementById('filters-container');
            const extraFilters = document.getElementById('mobile-extra-filters');
            const arrow = document.getElementById('filter-arrow');
            
            if (filtersContainer.classList.contains('hidden')) {
                filtersContainer.classList.remove('hidden');
                extraFilters.style.display = 'grid';
                arrow.style.transform = 'rotate(180deg)';
            } else {
                filtersContainer.classList.add('hidden');
                extraFilters.style.display = 'none';
                arrow.style.transform = 'rotate(0deg)';
            }
        }
    </script>
</div>