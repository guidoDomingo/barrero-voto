<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Votante;
use App\Models\Vehiculo;
use App\Models\Chofer;
use App\Models\Lider;
use App\Models\Viaje;
use App\Models\Distrito;
use App\Models\Zona;
use App\Models\Barrio;
use App\Services\TripPlannerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TripPlanner extends Component
{
    public $paso = 1; // 1: Seleccionar votantes, 2: Configurar viaje, 3: Resultado

    // Paso 1: Selección
    public $lider_id;
    public $votantesSeleccionados = [];
    public $filtroDistrito = '';
    public $filtroZona = '';
    public $filtroBarrio = '';
    
    // Para los dropdowns jerárquicos
    public $zonasDisponibles = [];
    public $barriosDisponibles = [];

    // Paso 2: Configuración
    public $vehiculo_id;
    public $chofer_id;
    public $fecha_viaje;
    public $hora_salida = '07:00';
    public $punto_partida;
    public $viaticos = 150000; // Costo fijo por defecto en guaraníes
    
    // Destino del viaje (usando datos maestros)
    public $distrito_destino_id;
    public $zona_destino_id;
    public $barrio_destino_id;

    // Paso 3: Resultado
    public $planGenerado = null;

    protected $rules = [
        'votantesSeleccionados' => 'required|array|min:1',
        'vehiculo_id' => 'required|exists:vehiculos,id',
        'chofer_id' => 'required|exists:choferes,id',
        'fecha_viaje' => 'required|date|after_or_equal:today',
        'hora_salida' => 'required',
        'punto_partida' => 'required|string|max:255',
        'viaticos' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        $user = Auth::user();
        
        // Solo asignar líder si el usuario es líder
        if ($user->esLider() && $user->lider) {
            $this->lider_id = $user->lider->id;
        }
        // Si es Admin, no filtrar por líder
        // Si es Veedor, no puede crear viajes (se controlará en la vista)

        $this->fecha_viaje = now()->addDays(1)->format('Y-m-d');
        $this->cargarZonasDisponibles();
        $this->cargarBarriosDisponibles();
    }

    public function updatedFiltroDistrito()
    {
        $this->filtroZona = '';
        $this->filtroBarrio = '';
        $this->distrito_destino_id = $this->filtroDistrito;
        $this->zona_destino_id = '';
        $this->barrio_destino_id = '';
        $this->cargarZonasDisponibles();
        $this->cargarBarriosDisponibles();
    }

    public function updatedFiltroZona()
    {
        $this->filtroBarrio = '';
        $this->zona_destino_id = $this->filtroZona;
        $this->barrio_destino_id = '';
        $this->cargarBarriosDisponibles();
    }

    public function updatedFiltroBarrio()
    {
        $this->barrio_destino_id = $this->filtroBarrio;
    }

    private function cargarZonasDisponibles()
    {
        if ($this->filtroDistrito) {
            $this->zonasDisponibles = Zona::where('distrito_id', $this->filtroDistrito)
                                          ->where('activo', true)
                                          ->orderBy('nombre')
                                          ->get();
        } else {
            $this->zonasDisponibles = Zona::where('activo', true)->orderBy('nombre')->get();
        }
    }

    private function cargarBarriosDisponibles()
    {
        if ($this->filtroZona) {
            $this->barriosDisponibles = Barrio::where('zona_id', $this->filtroZona)
                                              ->where('activo', true)
                                              ->orderBy('nombre')
                                              ->get();
        } elseif ($this->filtroDistrito) {
            $this->barriosDisponibles = Barrio::whereHas('zona', function($query) {
                                                  $query->where('distrito_id', $this->filtroDistrito);
                                              })
                                              ->where('activo', true)
                                              ->orderBy('nombre')
                                              ->get();
        } else {
            $this->barriosDisponibles = Barrio::where('activo', true)->orderBy('nombre')->get();
        }
    }

    public function toggleVotante($votanteId)
    {
        if (in_array($votanteId, $this->votantesSeleccionados)) {
            $this->votantesSeleccionados = array_diff($this->votantesSeleccionados, [$votanteId]);
        } else {
            $this->votantesSeleccionados[] = $votanteId;
        }
    }

    public function seleccionarTodos()
    {
        $votantes = $this->obtenerVotantesDisponibles()->pluck('id')->toArray();
        $this->votantesSeleccionados = $votantes;
    }

    public function limpiarSeleccion()
    {
        $this->votantesSeleccionados = [];
    }

    public function siguientePaso()
    {
        if ($this->paso === 1) {
            $this->validate([
                'votantesSeleccionados' => 'required|array|min:1',
            ], [
                'votantesSeleccionados.required' => 'Debe seleccionar al menos un votante',
                'votantesSeleccionados.min' => 'Debe seleccionar al menos un votante',
            ]);
            $this->paso = 2;
        } elseif ($this->paso === 2) {
            $this->generarPlan();
        }
    }

    public function pasoAnterior()
    {
        if ($this->paso > 1) {
            $this->paso--;
        }
    }

    public function generarPlan()
    {
        $this->validate();

        try {
            $vehiculo = Vehiculo::findOrFail($this->vehiculo_id);
            $chofer = Chofer::findOrFail($this->chofer_id);

            // Obtener votantes seleccionados
            $votantes = Votante::whereIn('id', $this->votantesSeleccionados)->get();

            if ($votantes->isEmpty()) {
                session()->flash('error', 'No hay votantes seleccionados.');
                return;
            }

            // Obtener información del destino
            $destinoInfo = $this->obtenerInformacionDestino();
            
            // Agrupar votantes por capacidad del vehículo
            $capacidad = $vehiculo->capacidad_pasajeros;
            $grupos = $votantes->chunk($capacidad);
            
            $planGrupos = [];
            $costoTotal = 0;

            foreach ($grupos as $index => $grupo) {
                // Usar solo el viático como costo fijo total
                $costoFijoViaje = $this->viaticos;
                
                $planGrupos[] = [
                    'numero_viaje' => $index + 1,
                    'destino' => $destinoInfo,
                    'votantes' => $grupo->map(function($v) {
                        return [
                            'id' => $v->id,
                            'nombres' => $v->nombres,
                            'apellidos' => $v->apellidos,
                            'barrio' => $v->barrio,
                        ];
                    })->toArray(),
                    'costo_fijo' => $costoFijoViaje,
                ];
                
                $costoTotal += $costoFijoViaje;
            }

            $this->planGenerado = [
                'grupos' => $planGrupos,
                'destino_completo' => $destinoInfo,
                'total_viajes' => count($planGrupos),
                'total_votantes' => $votantes->count(),
                'costo_total' => $costoTotal,
            ];

            $this->paso = 3;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al generar plan: ' . $e->getMessage());
        }
    }

    public function confirmarYGuardar()
    {
        if (!$this->planGenerado) {
            session()->flash('error', 'No hay plan generado para guardar.');
            return;
        }

        DB::beginTransaction();
        try {
            $chofer = Chofer::findOrFail($this->chofer_id);
            
            // Si no hay líder asignado, usar el primero disponible o null
            $liderResponsableId = $this->lider_id ?: optional(Lider::first())->id;

            foreach ($this->planGenerado['grupos'] as $grupo) {
                // Crear viaje
                $viaje = Viaje::create([
                    'vehiculo_id' => $this->vehiculo_id,
                    'chofer_id' => $this->chofer_id,
                    'lider_responsable_id' => $liderResponsableId,
                    'fecha_viaje' => $this->fecha_viaje,
                    'hora_salida' => $this->hora_salida,
                    'punto_partida' => $this->punto_partida,
                    'destino' => $destinoInfo['descripcion'] ?? 'Centro de votación',
                    'distancia_estimada_km' => 0, // No se calcula distancia
                    'costo_combustible' => 0, // No se calcula por separado
                    'costo_chofer' => 0, // No se calcula por separado
                    'viaticos' => $this->viaticos,
                    'costo_total' => $grupo['costo_fijo'],
                    'estado' => 'Planificado',
                ]);

                // Asociar votantes
                $votanteIds = collect($grupo['votantes'])->pluck('id')->toArray();
                $viaje->votantes()->attach($votanteIds);
            }

            DB::commit();
            
            session()->flash('message', 'Plan de viajes guardado exitosamente. ' . count($this->planGenerado['grupos']) . ' viaje(s) creado(s).');
            
            return redirect()->route('viajes.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al guardar viajes: ' . $e->getMessage());
            \Log::error('Error al guardar viajes: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    public function reiniciar()
    {
        $this->reset(['paso', 'votantesSeleccionados', 'planGenerado', 'filtroDistrito', 'filtroZona', 'filtroBarrio']);
        $this->fecha_viaje = now()->addDays(1)->format('Y-m-d');
        $this->viaticos = 150000; // Restablecer costo fijo por defecto
        $this->cargarZonasDisponibles();
        $this->cargarBarriosDisponibles();
    }

    private function obtenerVotantesDisponibles()
    {
        $user = Auth::user();
        $accesoIds = $user->liderIdsParaListas();

        $query = Votante::query()
            ->where('necesita_transporte', true)
            ->where('ya_voto', false);

        // Sin acceso = colección vacía
        if ($accesoIds !== null && $accesoIds->isEmpty()) {
            $query->whereRaw('1 = 0');
        }

        if ($this->lider_id) {
            $query->where('lider_asignado_id', $this->lider_id);
        }

        return $query->orderBy('apellidos')->orderBy('nombres')->get();
    }

    private function obtenerInformacionDestino()
    {
        $destino = [];
        
        if ($this->distrito_destino_id) {
            $distrito = Distrito::find($this->distrito_destino_id);
            $destino['distrito'] = $distrito ? $distrito->nombre : '';
        }
        
        if ($this->zona_destino_id) {
            $zona = Zona::find($this->zona_destino_id);
            $destino['zona'] = $zona ? $zona->nombre : '';
        }
        
        if ($this->barrio_destino_id) {
            $barrio = Barrio::find($this->barrio_destino_id);
            $destino['barrio'] = $barrio ? $barrio->nombre : '';
        }
        
        // Construir descripción del destino
        $partes = array_filter($destino);
        $destino['descripcion'] = !empty($partes) ? implode(', ', $partes) : 'Centro de votación';
        
        return $destino;
    }

    public function obtenerEstadisticasFiltros()
    {
        $totalVotantes = Votante::count();
        $necesitanTransporte = Votante::where('necesita_transporte', true)->count();
        $yaVotaron = Votante::where('ya_voto', true)->count();
        $disponibles = Votante::where('necesita_transporte', true)
                              ->where('ya_voto', false)
                              ->count();

        return [
            'total' => $totalVotantes,
            'necesitan_transporte' => $necesitanTransporte,
            'ya_votaron' => $yaVotaron,
            'disponibles' => $disponibles,
            'excluidos' => $totalVotantes - $disponibles
        ];
    }

    public function render()
    {
        $votantesDisponibles = $this->obtenerVotantesDisponibles();
        $vehiculos = Vehiculo::where('disponible', true)->get();
        $choferes = Chofer::where('disponible', true)->get();
        $lideres = Lider::with('usuario')->get();
        
        // Obtener distritos, zonas y barrios de los datos maestros
        $distritosDisponibles = Distrito::where('activo', true)->orderBy('nombre')->get();
        
        // Obtener estadísticas de filtros
        $estadisticasFiltros = $this->obtenerEstadisticasFiltros();

        return view('livewire.trip-planner', [
            'votantesDisponibles' => $votantesDisponibles,
            'vehiculos' => $vehiculos,
            'choferes' => $choferes,
            'lideres' => $lideres,
            'distritosDisponibles' => $distritosDisponibles,
            'zonasDisponibles' => $this->zonasDisponibles,
            'barriosDisponibles' => $this->barriosDisponibles,
            'estadisticasFiltros' => $estadisticasFiltros,
        ])->layout('layouts.app');
    }
}
