<div>
    <style>
        .animation-delay-100 { animation-delay: 0.1s; }
        .animation-delay-200 { animation-delay: 0.2s; }
        .animation-delay-300 { animation-delay: 0.3s; }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .bg-gradient-animated {
            background: linear-gradient(-45deg, #3B82F6, #1D4ED8, #2563EB, #1E40AF);
            background-size: 400% 400%;
            animation: gradientShift 2s ease infinite;
        }
    </style>

    <!-- Loading Overlay -->
    <div wire:loading class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center" style="z-index: 9999;">
        <div class="bg-white rounded-lg shadow-xl p-8 max-w-md mx-4 transform transition-all">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-6 relative">
                    <svg class="animate-spin h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <!-- Pulse animation overlay -->
                    <div class="absolute inset-0 bg-blue-200 rounded-full animate-ping opacity-20"></div>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Importando votantes del TSJE</h3>
                
                <!-- Progress steps with animations -->
                <div class="space-y-3 text-left bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-blue-600 rounded-full mr-3 animate-pulse"></div>
                        <span class="text-gray-700">📊 Leyendo archivo Excel...</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-blue-400 rounded-full mr-3 animate-pulse animation-delay-100"></div>
                        <span class="text-gray-700">✅ Validando datos de votantes</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-blue-300 rounded-full mr-3 animate-pulse animation-delay-200"></div>
                        <span class="text-gray-700">💾 Guardando en base de datos</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <div class="w-2 h-2 bg-blue-200 rounded-full mr-3 animate-pulse animation-delay-300"></div>
                        <span class="text-gray-700">🏗️ Asignando a líder territorial</span>
                    </div>
                </div>
                
                <!-- Progress bar -->
                <div class="w-full bg-gray-200 rounded-full h-3 mb-4 overflow-hidden">
                    <div class="bg-gradient-animated h-3 rounded-full" style="width: 100%"></div>
                </div>
                
                <div class="text-center">
                    <p class="text-blue-600 font-medium text-sm">⏳ Este proceso puede tomar unos momentos</p>
                    <p class="text-gray-500 text-xs mt-1">Por favor, no cierre esta ventana</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Importar Votantes</h1>
        <p class="mt-1 text-sm text-gray-600">Importe masivamente votantes desde archivos CSV o Excel</p>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div role="alert" class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('info'))
        <div role="alert" class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded relative">
            <div class="flex">
                <svg class="h-5 w-5 text-blue-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                {{ session('info') }}
            </div>
        </div>
    @endif

    <!-- Formato TSJE Detectado -->
    @if ($es_formato_tsje)
        <div class="mb-4 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-emerald-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 class="font-medium text-emerald-900">📊 Formato TSJE detectado</h4>
                    <p class="text-sm text-emerald-700 mt-1">
                        Su archivo contiene datos completos del Tribunal Superior de Justicia Electoral. 
                        Se importarán todos los datos disponibles incluyendo: número de registro, departamento, distrito, 
                        local de votación, mesa, orden, fecha de afiliación, etc.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Column -->
        <div class="lg:col-span-2">
            <form wire:submit="importar">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Configuración de Importación</h3>

                    <!-- File Upload -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Archivo CSV o Excel *</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                            <input wire:model="archivo" type="file" accept=".csv,.xlsx,.xls" 
                                class="hidden" id="archivo">
                            <label for="archivo" class="cursor-pointer">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <div class="mt-2">
                                    @if ($archivo)
                                        <p class="text-sm font-medium text-gray-900">{{ $archivo->getClientOriginalName() }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($archivo->getSize() / 1024, 2) }} KB</p>
                                        
                                        <!-- Progress indicator durante la carga -->
                                        <div wire:loading class="mt-3">
                                            <div class="w-full bg-blue-100 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                                            </div>
                                            <p class="text-xs text-blue-600 font-medium mt-1">Procesando archivo...</p>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-600">
                                            <span class="text-blue-600 font-medium">Haga clic para seleccionar</span> o arrastre un archivo
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">CSV o Excel (XLSX, XLS) hasta 10MB</p>
                                    @endif
                                </div>
                            </label>
                        </div>
                        @error('archivo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Líder Assignment -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Asignar a Líder *</label>
                        <select wire:model="lider_asignado_id"  
                                wire:loading.attr="disabled"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">Seleccione un líder</option>
                            @foreach($lideres as $lider)
                                <option value="{{ $lider->id }}">{{ $lider->usuario->name }} - {{ $lider->territorio }}</option>
                            @endforeach
                        </select>
                        @error('lider_asignado_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Opciones de Importación -->
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Opciones de Procesamiento</h4>
                        
                        <!-- Consultar TSJE -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 {{ $es_formato_tsje ? 'opacity-50' : '' }}">
                            <label class="flex items-start">
                                <input type="checkbox" wire:model="consultar_tsje" 
                                       {{ $es_formato_tsje ? 'disabled' : '' }}
                                       class="mt-1 mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500 {{ $es_formato_tsje ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <div>
                                    <div class="font-medium text-blue-900">
                                        🔍 Consultar automáticamente en TSJE
                                        @if($es_formato_tsje)
                                            <span class="text-xs bg-gray-500 text-white px-2 py-1 rounded ml-2">DESHABILITADO</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-blue-700 mt-1">
                                        @if($es_formato_tsje)
                                            Deshabilitado porque su archivo ya contiene datos completos del TSJE.
                                        @else
                                            El sistema buscará automáticamente nombres, apellidos y dirección en las bases de datos del Tribunal Superior de Justicia Electoral para cada CI importado.
                                        @endif
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Solo CI -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 {{ $es_formato_tsje ? 'opacity-50' : '' }}">
                            <label class="flex items-start">
                                <input type="checkbox" wire:model="solo_ci_importar" 
                                       {{ $es_formato_tsje ? 'disabled' : '' }}
                                       class="mt-1 mr-3 rounded border-gray-300 text-green-600 focus:ring-green-500 {{ $es_formato_tsje ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <div>
                                    <div class="font-medium text-green-900">
                                        📋 Solo importar CIs y buscar datos completos
                                        @if($es_formato_tsje)
                                            <span class="text-xs bg-gray-500 text-white px-2 py-1 rounded ml-2">DESHABILITADO</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-green-700 mt-1">
                                        @if($es_formato_tsje)
                                            Deshabilitado porque su archivo ya contiene datos completos.
                                        @else
                                            Ideal si solo tiene una lista de cédulas. El sistema completará automáticamente nombres, direcciones y datos de votación desde el TSJE.
                                        @endif
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Actualizar Duplicados -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 {{ $es_formato_tsje ? 'opacity-75' : '' }}">
                            <label class="flex items-start">
                                <input type="checkbox" wire:model="actualizar_duplicados" 
                                       wire:loading.attr="disabled"
                                       class="mt-1 mr-3 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <div>
                                    <div class="font-medium text-yellow-900 flex items-center">
                                        🔄 Actualizar votantes existentes
                                        <span wire:loading class="ml-2">
                                            <svg class="animate-spin h-4 w-4 text-yellow-600" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="text-sm text-yellow-700 mt-1">
                                        Si encuentra un CI que ya existe, actualizará la información en lugar de crear un duplicado.
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <button type="submit" 
                                wire:loading.attr="disabled"
                                class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove>
                                Importar Votantes
                            </span>
                            <span wire:loading class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Procesando archivo...
                            </span>
                        </button>
                        
                        <button wire:click.prevent="limpiar" type="button" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Limpiar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Results -->
            @if($resultado)
                <div class="mt-6 bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Resultado de la Importación</h3>
                    
                    @if(isset($resultado['exito']) && $resultado['exito'])
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <span class="text-sm font-medium text-gray-700">Total procesados</span>
                                <span class="text-lg font-bold text-blue-600">{{ $resultado['total_procesados'] }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <span class="text-sm font-medium text-gray-700">Nuevos registros</span>
                                <span class="text-lg font-bold text-green-600">{{ $resultado['nuevos'] }}</span>
                            </div>
                            
                            @if($resultado['actualizados'] > 0)
                                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                    <span class="text-sm font-medium text-gray-700">Actualizados</span>
                                    <span class="text-lg font-bold text-yellow-600">{{ $resultado['actualizados'] }}</span>
                                </div>
                            @endif
                            
                            @if($resultado['duplicados'] > 0)
                                <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                                    <span class="text-sm font-medium text-gray-700">Duplicados omitidos</span>
                                    <span class="text-lg font-bold text-orange-600">{{ $resultado['duplicados'] }}</span>
                                </div>
                            @endif
                            
                            @if($resultado['fallidos'] > 0)
                                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                    <span class="text-sm font-medium text-gray-700">Fallidos</span>
                                    <span class="text-lg font-bold text-red-600">{{ $resultado['fallidos'] }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Errors Details -->
                        @if(count($resultado['errores'] ?? []) > 0)
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Errores:</h4>
                                <div class="max-h-48 overflow-y-auto bg-red-50 border border-red-200 rounded p-3">
                                    @foreach($resultado['errores'] as $error)
                                        <p class="text-xs text-red-800 mb-1">
                                            Fila {{ $error['fila'] }}: {{ implode(', ', $error['errores']) }}
                                        </p>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-sm text-red-800">
                                {{ $resultado['error'] ?? $resultado['mensaje'] ?? 'Error desconocido en la importación' }}
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Info Column -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Plantilla de Importación</h3>
                <p class="text-sm text-gray-600 mb-4">
                    @if($es_formato_tsje)
                        Su archivo ya tiene el formato correcto del TSJE.
                    @else
                        Descargue la plantilla Excel de ejemplo para ver el formato correcto
                    @endif
                </p>
                
                @if(!$es_formato_tsje)
                    <a href="{{ route('votantes.plantilla') }}" 
                       class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Descargar Plantilla Excel
                    </a>
                @else
                    <div class="bg-emerald-100 border border-emerald-200 rounded-lg p-3">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-emerald-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium text-emerald-800">Formato TSJE detectado</span>
                        </div>
                        <p class="text-xs text-emerald-700 mt-2">
                            Su archivo contiene las columnas necesarias del Tribunal Superior de Justicia Electoral.
                        </p>
                    </div>
                @endif
            </div>

            <div class="{{ $es_formato_tsje ? 'bg-emerald-50' : 'bg-blue-50' }} rounded-lg p-6">
                <h3 class="text-sm font-medium {{ $es_formato_tsje ? 'text-emerald-900' : 'text-blue-900' }} mb-3">
                    @if($es_formato_tsje)
                        Campos detectados en su Excel TSJE
                    @else
                        Formato del archivo
                    @endif
                </h3>
                <ul class="text-xs {{ $es_formato_tsje ? 'text-emerald-800' : 'text-blue-800' }} space-y-2">
                    @if($es_formato_tsje)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Datos básicos: CI, nombres, apellidos, dirección</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Ubicación electoral: departamento, distrito, local, mesa, orden</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Fechas: nacimiento y afiliación partidaria</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Puede agregar: teléfono, email, intención de voto, notas</span>
                        </li>
                    @else
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Primera fila debe contener los nombres de columnas</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Campos obligatorios: ci, nombres, apellidos</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>codigo_intencion debe ser: A, B, C, D o E</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>necesita_transporte: Si/No o 1/0</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Coordenadas geográficas son opcionales pero recomendadas</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
