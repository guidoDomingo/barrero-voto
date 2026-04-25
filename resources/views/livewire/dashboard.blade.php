<div>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Votantes -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-blue-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Votantes</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900">
                                {{ $metricas['total_votantes'] ?? 0 }}
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Ya Votaron -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-green-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Ya Votaron</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900">
                                {{ $metricas['ya_votaron'] ?? 0 }}
                            </div>
                            <div class="ml-2 text-sm text-gray-600">
                                ({{ number_format($metricas['porcentaje_votacion'] ?? 0, 1) }}%)
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Contactados -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-yellow-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Contactados</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900">
                                {{ $metricas['contactados'] ?? 0 }}
                            </div>
                            @if(isset($metricas['total_votantes']) && $metricas['total_votantes'] > 0)
                            <div class="ml-2 text-sm text-gray-600">
                                ({{ number_format(($metricas['contactados'] / $metricas['total_votantes']) * 100, 1) }}%)
                            </div>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Votos Estimados -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="rounded-md bg-purple-500 p-3">
                        <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Votos Estimados</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900">
                                {{ number_format($prediccion['votos_estimados'] ?? 0, 1) }}
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Intención de Voto -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Intención de Voto</h3>
            <div class="space-y-3">
                @php
                    $colores = ['A' => 'bg-green-500', 'B' => 'bg-blue-500', 'C' => 'bg-yellow-500', 'D' => 'bg-orange-500', 'E' => 'bg-red-500'];
                    $total = array_sum($metricas['por_intencion'] ?? []);
                @endphp
                @foreach(($metricas['por_intencion'] ?? []) as $codigo => $cantidad)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ $codigo }}</span>
                            <span class="text-sm text-gray-600">{{ $cantidad }} ({{ $total > 0 ? number_format(($cantidad / $total) * 100, 1) : 0 }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="{{ $colores[$codigo] }} h-2 rounded-full" style="width: {{ $total > 0 ? ($cantidad / $total) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Rendimiento de Líderes -->
        @if(isset($metricas['votos_por_lider']))
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Rendimiento por Líder</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Líder</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Candidato</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Votantes</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Votaron</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-2">% Votos</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Con PC</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Efic. PC</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach(($metricas['votos_por_lider']->take(8) ?? []) as $lider)
                        <tr>
                            <td class="py-2 pr-3">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-sm mr-2">
                                        {{ strtoupper(substr($lider->usuario->name, 0, 1)) }}
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 truncate max-w-28">
                                        {{ $lider->usuario->name }}
                                    </div>
                                </div>
                            </td>
                            <td class="py-2 pr-3">
                                @if($lider->candidato)
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 font-medium">
                                        {{ $lider->candidato->usuario->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 italic">Sin candidato</span>
                                @endif
                            </td>
                            <td class="text-right py-2 text-sm text-gray-900">{{ $lider->total_votantes }}</td>
                            <td class="text-right py-2 text-sm font-semibold text-green-600">{{ $lider->votantes_que_votaron }}</td>
                            <td class="text-right py-2 text-sm text-gray-600">
                                {{ $lider->total_votantes > 0 ? number_format(($lider->votantes_que_votaron / $lider->total_votantes) * 100, 1) : 0 }}%
                            </td>
                            <td class="text-right py-2 text-sm text-blue-600">{{ $lider->votantes_con_pc }}</td>
                            <td class="text-right py-2 text-sm text-indigo-600">
                                {{ $lider->votantes_con_pc > 0 ? number_format(($lider->votos_con_pc / $lider->votantes_con_pc) * 100, 0) : 0 }}%
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Líderes Top (si no hay datos de PC móvil) -->
        @if(count($lideresTop ?? []) > 0 && !isset($metricas['votos_por_lider']))
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Líderes Top</h3>
            <div class="space-y-3">
                @foreach($lideresTop as $lider)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                {{ strtoupper(substr($lider->usuario->name, 0, 1)) }}
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ $lider->usuario->name }}</p>
                                <p class="text-xs text-gray-500">{{ $lider->territorio }}</p>
                            </div>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">
                            {{ $lider->votantes_count }} votantes
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Resumen por Candidato (solo admin) -->
    @if(count($candidatosResumen ?? []) > 0)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumen por Candidato</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2 pr-6">Candidato</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2 pr-6">Partido</th>
                        <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-2 pr-6">Líderes</th>
                        <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-2 pr-6">Total Votantes</th>
                        <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-2 pr-6">Ya Votaron</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Progreso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($candidatosResumen as $c)
                    <tr>
                        <td class="py-3 pr-6">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-700 font-bold text-sm mr-2">
                                    {{ strtoupper(substr($c['nombre'], 0, 1)) }}
                                </div>
                                <span class="text-sm font-semibold text-gray-900">{{ $c['nombre'] }}</span>
                            </div>
                        </td>
                        <td class="py-3 pr-6 text-sm text-gray-600">{{ $c['partido'] ?: '—' }}</td>
                        <td class="py-3 pr-6 text-right text-sm text-gray-900 font-medium">{{ $c['lideres'] }}</td>
                        <td class="py-3 pr-6 text-right text-sm text-gray-900">{{ number_format($c['total_votantes']) }}</td>
                        <td class="py-3 pr-6 text-right text-sm font-semibold text-green-600">{{ number_format($c['ya_votaron']) }}</td>
                        <td class="py-3 min-w-40">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $c['porcentaje'] }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600 w-10 text-right">{{ $c['porcentaje'] }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Tables Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Viajes Próximos -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Viajes Próximos</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehículo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($viajesProximos as $viaje)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($viaje->fecha_viaje)->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $viaje->vehiculo->placa }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        @if($viaje->estado === 'Planificado') bg-blue-100 text-blue-800
                                        @elseif($viaje->estado === 'Confirmado') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $viaje->estado }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay viajes próximos
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gastos Recientes -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Gastos Recientes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($gastosRecientes as $gasto)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($gasto->fecha_gasto)->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $gasto->categoria }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ₲ {{ number_format($gasto->monto, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No hay gastos registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
