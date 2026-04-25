<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <!-- Header -->
            <div class="bg-gray-50 p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gestión de Usuarios</h1>
                        <p class="text-gray-600">Administra usuarios, roles y permisos del sistema</p>
                    </div>
                    <button wire:click="nuevoUsuario"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Nuevo Usuario
                    </button>
                </div>
            </div>

            <!-- Alerts -->
            @if (session()->has('message'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3">
                    {{ session('message') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Filtros -->
            <div class="p-6 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" wire:model.live="search" placeholder="Nombre, email o CI..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                        <select wire:model.live="filtroRol" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select wire:model.live="filtroEstado" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button wire:click="limpiarFiltros" 
                                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de usuarios -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Candidato</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último acceso</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($usuarios as $usuario)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                                                {{ substr($usuario->name, 0, 2) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $usuario->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $usuario->email }}</div>
                                            @if($usuario->ci)
                                                <div class="text-xs text-gray-400">CI: {{ $usuario->ci }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($usuario->role)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($usuario->role->slug === 'admin') bg-purple-100 text-purple-800
                                            @elseif($usuario->role->slug === 'candidato') bg-yellow-100 text-yellow-800
                                            @elseif($usuario->role->slug === 'lider') bg-blue-100 text-blue-800
                                            @elseif($usuario->role->slug === 'veedor') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ $usuario->role->nombre }}
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Sin rol
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    @php
                                        // Para líderes, el candidato viene de lideres.candidato_id; para el resto, de users.candidato_id
                                        $candidatoNombre = null;
                                        if ($usuario->role?->slug === 'lider' && $usuario->lider?->candidato?->usuario) {
                                            $candidatoNombre = $usuario->lider->candidato->usuario->name;
                                        } elseif ($usuario->role?->slug !== 'candidato' && $usuario->candidatoAsignado?->usuario) {
                                            $candidatoNombre = $usuario->candidatoAsignado->usuario->name;
                                        }
                                    @endphp
                                    @if($candidatoNombre)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            {{ $candidatoNombre }}
                                        </span>
                                    @elseif($usuario->role?->slug === 'candidato')
                                        <span class="text-xs text-gray-400 italic">Es candidato</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($usuario->activo)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Activo
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($usuario->ultimo_acceso)
                                        {{ $usuario->ultimo_acceso->diffForHumans() }}
                                    @else
                                        Nunca
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <button wire:click="editarUsuario({{ $usuario->id }})" 
                                                class="text-blue-600 hover:text-blue-900" title="Editar">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg>
                                        </button>
                                        
                                        @if($usuario->id !== auth()->id())
                                            <button wire:click="toggleEstado({{ $usuario->id }})" 
                                                    wire:confirm="¿Estás seguro de cambiar el estado de este usuario?"
                                                    class="{{ $usuario->activo ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' }}" 
                                                    title="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}">
                                                @if($usuario->activo)
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                            </button>
                                            
                                            <button wire:click="eliminarUsuario({{ $usuario->id }})" 
                                                    wire:confirm="¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer."
                                                    class="text-red-600 hover:text-red-900" title="Eliminar">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        @else
                                            <span class="text-gray-400" title="No puedes modificar tu propia cuenta">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No se encontraron usuarios
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $usuarios->links() }}
            </div>
        </div>
    </div>

    <!-- Modal para crear/editar usuario -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="relative mx-auto p-6 border max-w-4xl w-full shadow-lg rounded-lg bg-white max-h-[90vh] overflow-y-auto">
                <div class="mt-3">
                    <!-- Header del modal -->
                    <div class="flex justify-between items-center pb-3 border-b">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $editingUser ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
                        </h3>
                        <button wire:click="cerrarModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Formulario -->
                    <form wire:submit.prevent="guardarUsuario" class="mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Nombre -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                                <input type="text" wire:model="name" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                <input type="email" wire:model="email" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                                @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- CI -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CI</label>
                                <input type="text" wire:model="ci" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('ci') border-red-500 @enderror">
                                @error('ci') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Teléfono -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                                <input type="text" wire:model="telefono" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('telefono') border-red-500 @enderror">
                                @error('telefono') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Rol -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
                                <select wire:model.live="role_id"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('role_id') border-red-500 @enderror">
                                    <option value="">Seleccionar rol</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('role_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- Estado -->
                            <div class="flex items-center">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="activo" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Usuario activo</span>
                                </label>
                            </div>

                            <!-- Campo Candidato (para todos los roles excepto el candidato mismo) -->
                            @if(!$this->es_rol_candidato && $role_id)
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Candidato asignado</label>
                                <select wire:model="candidato_id"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sin candidato asignado</option>
                                    @foreach($candidatos as $candidato)
                                        <option value="{{ $candidato->id }}">{{ $candidato->usuario->name }}{{ $candidato->partido ? ' (' . $candidato->partido . ')' : '' }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Candidato al que pertenece este usuario</p>
                            </div>
                            @endif

                            <!-- Campo Partido (solo para candidatos) -->
                            @if($this->es_rol_candidato)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Partido político</label>
                                <input type="text" wire:model="partido" placeholder="Ej: Partido Colorado"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            @endif

                            <!-- Campos de contraseña (ocultar solo para rol líder) -->
                            @if(!$this->es_rol_lider)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Contraseña {{ $editingUser ? '' : '*' }}
                                </label>
                                <input type="password" wire:model="password" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                                @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                @if($editingUser)
                                    <p class="text-xs text-gray-500 mt-1">Dejar en blanco para mantener la contraseña actual</p>
                                @endif
                            </div>

                            <!-- Confirmar contraseña -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirmar contraseña {{ $editingUser ? '' : '*' }}
                                </label>
                                <input type="password" wire:model="password_confirmation" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            @else
                            <!-- Mensaje informativo para líder -->
                            <div class="col-span-2 bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-blue-800">Contraseña automática para líderes</p>
                                        <p class="text-xs text-blue-600">Se generará automáticamente usando: nombre + últimos 4 dígitos del CI</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                            <button type="button" wire:click="cerrarModal"
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                                {{ $editingUser ? 'Actualizar' : 'Crear' }} Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
