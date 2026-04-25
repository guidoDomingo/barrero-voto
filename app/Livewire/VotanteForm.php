<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Votante;
use App\Models\Lider;
use App\Services\TSJEService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VotanteForm extends Component
{
    public $votanteId = null;
    public $ci;
    public $nombres;
    public $apellidos;
    public $telefono;
    public $email;
    public $direccion;
    public $barrio;
    public $zona;
    public $distrito;
    public $lider_asignado_id;
    public $codigo_intencion = 'C';
    public $estado_contacto = 'Nuevo';
    public $necesita_transporte = false;
    public $latitud;
    public $longitud;
    public $notas;
    
    // Campos adicionales del Excel TSJE
    public $nro_registro;
    public $codigo_departamento;
    public $departamento;
    public $codigo_distrito;
    public $codigo_seccion;
    public $seccion;
    public $codigo_barrio;
    public $barrio_tsje;
    public $local_votacion;
    public $descripcion_local;
    public $mesa;
    public $orden;
    public $fecha_nacimiento;
    public $fecha_afiliacion;
    
    public $buscandoDatos = false;
    public $datosEncontrados = false;
    public $mensajeBusqueda = '';
    public $busquedaAutomatica = true;
    public $ultimaBusqueda = '';

    protected function rules()
    {
        $rules = [
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:255',
            'barrio' => 'nullable|string|max:100',
            'zona' => 'nullable|string|max:100',
            'distrito' => 'nullable|string|max:100',
            'lider_asignado_id' => 'required|exists:lideres,id',
            'codigo_intencion' => 'required|in:A,B,C,D,E',
            'estado_contacto' => 'required|in:Nuevo,Contactado,Re-contacto,Comprometido,Crítico',
            'necesita_transporte' => 'boolean',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'notas' => 'nullable|string|max:1000',
            
            // Reglas para campos TSJE
            'nro_registro' => 'nullable|string|max:20',
            'codigo_departamento' => 'nullable|string|max:10',
            'departamento' => 'nullable|string|max:100',
            'codigo_distrito' => 'nullable|string|max:10',
            'codigo_seccion' => 'nullable|string|max:10',
            'seccion' => 'nullable|string|max:100',
            'codigo_barrio' => 'nullable|string|max:10',
            'barrio_tsje' => 'nullable|string|max:100',
            'local_votacion' => 'nullable|string|max:200',
            'descripcion_local' => 'nullable|string|max:300',
            'mesa' => 'nullable|string|max:10',
            'orden' => 'nullable|integer|min:1',
            'fecha_nacimiento' => 'nullable|date',
            'fecha_afiliacion' => 'nullable|date',
        ];

        if ($this->votanteId) {
            $rules['ci'] = 'required|string|max:20|unique:votantes,ci,' . $this->votanteId;
        } else {
            $rules['ci'] = 'required|string|max:20|unique:votantes,ci';
        }

        return $rules;
    }

    protected $messages = [
        'nombres.required' => 'El nombre es obligatorio',
        'apellidos.required' => 'El apellido es obligatorio',
        'ci.required' => 'El CI es obligatorio',
        'ci.unique' => 'Ya existe un votante con este CI',
        'lider_asignado_id.required' => 'Debe asignar un líder',
        'codigo_intencion.required' => 'El código de intención es obligatorio',
    ];

    public function mount($votanteId = null)
    {
        if ($votanteId) {
            $this->cargarVotante($votanteId);
        } else {
            $user = Auth::user();
            if ($user->esLider() && $user->lider) {
                $this->lider_asignado_id = $user->lider->id;
            } elseif ($user->esCandidato() && $user->candidato) {
                $lideres = $user->candidato->lideres;
                if ($lideres->count() === 1) {
                    $this->lider_asignado_id = $lideres->first()->id;
                }
            }
        }
    }

    public function updatedCi($value)
    {
        // Limpiar el CI (solo números)
        $ci = preg_replace('/[^0-9]/', '', $value);
        $this->ci = $ci;
        
        // Resetear mensajes si el CI cambió
        if ($ci !== $this->ultimaBusqueda) {
            $this->datosEncontrados = false;
            $this->mensajeBusqueda = '';
            $this->ultimaBusqueda = '';
        }
        
        // Búsqueda automática si el CI tiene al menos 6 dígitos y búsqueda automática está habilitada
        if ($this->busquedaAutomatica && strlen($ci) >= 6 && $ci !== $this->ultimaBusqueda) {
            $this->buscarVotanteLocal();
        }
    }

    public function forzarActualizacion()
    {
        Log::info("Forzando actualización manual del componente");
        // Este método público puede ser llamado desde JavaScript
        return;
    }
    
    public function buscarVotanteLocal()
    {
        if (empty($this->ci) || strlen($this->ci) < 6) {
            return;
        }
        
        $this->ultimaBusqueda = $this->ci;
        $this->buscandoDatos = true;
        $this->mensajeBusqueda = '🔍 Buscando en base de datos local...';
        
        try {
            // Buscar votante existente en la base de datos local
            $votanteExistente = Votante::where('ci', $this->ci)
                ->where('id', '!=', $this->votanteId) // Excluir el votante actual si estamos editando
                ->first();
            
            if ($votanteExistente) {
                Log::info("Votante encontrado para CI: {$this->ci}", [
                    'id' => $votanteExistente->id,
                    'nombres' => $votanteExistente->nombres,
                    'apellidos' => $votanteExistente->apellidos
                ]);
                
                // Usar la MISMA lógica que cargarVotante() - que SÍ funciona
                $this->nombres = $votanteExistente->nombres;
                $this->apellidos = $votanteExistente->apellidos;
                $this->telefono = $votanteExistente->telefono;
                $this->email = $votanteExistente->email;
                $this->direccion = $votanteExistente->direccion;
                $this->barrio = $votanteExistente->barrio;
                $this->zona = $votanteExistente->zona;
                $this->distrito = $votanteExistente->distrito;
                $this->latitud = $votanteExistente->latitud;
                $this->longitud = $votanteExistente->longitud;
                
                // Datos electorales TSJE
                $this->nro_registro = $votanteExistente->nro_registro;
                $this->codigo_departamento = $votanteExistente->codigo_departamento;
                $this->departamento = $votanteExistente->departamento;
                $this->codigo_distrito = $votanteExistente->codigo_distrito;
                $this->codigo_seccion = $votanteExistente->codigo_seccion;
                $this->seccion = $votanteExistente->seccion;
                $this->codigo_barrio = $votanteExistente->codigo_barrio;
                $this->barrio_tsje = $votanteExistente->barrio_tsje;
                $this->local_votacion = $votanteExistente->local_votacion;
                $this->descripcion_local = $votanteExistente->descripcion_local;
                $this->mesa = $votanteExistente->mesa;
                $this->orden = $votanteExistente->orden;
                $this->fecha_nacimiento = $votanteExistente->fecha_nacimiento ? $votanteExistente->fecha_nacimiento->format('Y-m-d') : null;
                $this->fecha_afiliacion = $votanteExistente->fecha_afiliacion ? $votanteExistente->fecha_afiliacion->format('Y-m-d') : null;
                
                // Datos de campaña - solo si estamos en modo creación
                if (!$this->votanteId) {
                    $this->lider_asignado_id = $votanteExistente->lider_asignado_id;
                    $this->codigo_intencion = $votanteExistente->codigo_intencion;
                    $this->estado_contacto = $votanteExistente->estado_contacto;
                    $this->necesita_transporte = $votanteExistente->necesita_transporte;
                    $this->notas = $votanteExistente->notas;
                }
                
                $this->datosEncontrados = true;
                $this->buscandoDatos = false;
                $this->mensajeBusqueda = '✅ Votante encontrado en la base de datos local - Datos cargados automáticamente';
                
                // Si no tenemos líder asignado, asignar según rol
                if (!$this->lider_asignado_id) {
                    $user = Auth::user();
                    if ($user->esLider() && $user->lider) {
                        $this->lider_asignado_id = $user->lider->id;
                    } elseif ($user->esCandidato() && $user->candidato) {
                        $lideres = $user->candidato->lideres;
                        if ($lideres->count() === 1) {
                            $this->lider_asignado_id = $lideres->first()->id;
                        }
                    }
                }
                
                Log::info("Datos cargados exitosamente", [
                    'ci' => $this->ci,
                    'nombres' => $this->nombres,
                    'apellidos' => $this->apellidos
                ]);
                
                // Disparar evento con TODOS los datos para JavaScript
                $this->dispatch('votante-encontrado-datos', [
                    'accion' => 'forzar-actualizacion',
                    'datos' => [
                        // Datos básicos
                        'nombres' => $this->nombres,
                        'apellidos' => $this->apellidos,
                        'ci' => $this->ci,
                        'telefono' => $this->telefono,
                        'email' => $this->email,
                        'direccion' => $this->direccion,
                        'barrio' => $this->barrio,
                        'zona' => $this->zona,
                        'distrito' => $this->distrito,
                        'latitud' => $this->latitud,
                        'longitud' => $this->longitud,
                        // Datos electorales
                        'nro_registro' => $this->nro_registro,
                        'codigo_departamento' => $this->codigo_departamento,
                        'departamento' => $this->departamento,
                        'codigo_distrito' => $this->codigo_distrito,
                        'codigo_seccion' => $this->codigo_seccion,
                        'seccion' => $this->seccion,
                        'codigo_barrio' => $this->codigo_barrio,
                        'barrio_tsje' => $this->barrio_tsje,
                        'local_votacion' => $this->local_votacion,
                        'descripcion_local' => $this->descripcion_local,
                        'mesa' => $this->mesa,
                        'orden' => $this->orden,
                        'fecha_nacimiento' => $this->fecha_nacimiento,
                        'fecha_afiliacion' => $this->fecha_afiliacion,
                        // Datos de campaña
                        'lider_asignado_id' => $this->lider_asignado_id,
                        'codigo_intencion' => $this->codigo_intencion,
                        'estado_contacto' => $this->estado_contacto,
                        'necesita_transporte' => $this->necesita_transporte,
                        'notas' => $this->notas
                    ]
                ]);
                
                return;
            }
            
            // Si no encuentra en la base local
            $this->buscandoDatos = false;
            $this->mensajeBusqueda = '❌ No se encontró el votante en la base de datos local';
            
        } catch (\Exception $e) {
            Log::error('Error al buscar votante local: ' . $e->getMessage());
            $this->buscandoDatos = false;
            $this->mensajeBusqueda = '❌ Error al buscar en la base de datos local';
        }
    }

    public function consultarTSJEAutomatico()
    {
        if (empty($this->ci) || strlen($this->ci) < 6) {
            return;
        }
        
        $this->buscandoDatos = true;
        $this->mensajeBusqueda = '🔍 No encontrado localmente. Buscando en TSJE...';
        
        // Usar un delay pequeño para evitar múltiples búsquedas
        $this->dispatch('buscar-datos', ci: $this->ci);
    }
    
    public function buscarVotanteManual()
    {
        if (empty($this->ci)) {
            $this->mensajeBusqueda = '⚠️ Ingrese un número de cédula';
            return;
        }
        
        $this->ultimaBusqueda = $this->ci;
        $this->buscarVotanteLocal();
    }
    
    public function consultarTSJE()
    {
        if (empty($this->ci)) {
            $this->mensajeBusqueda = '⚠️ Ingrese un número de cédula';
            return;
        }
        
        $this->ultimaBusqueda = $this->ci;
        $this->buscandoDatos = true;
        $this->datosEncontrados = false;
        $this->mensajeBusqueda = '🔍 Consultando datos en TSJE y bases de datos públicas...';
        
        try {
            $tsje = new TSJEService();
            $datos = $tsje->consultarVotante($this->ci);
            
            if ($datos && $datos['encontrado']) {
                // Solo llenar campos vacíos para no sobrescribir datos ya ingresados
                if (empty($this->nombres)) {
                    $this->nombres = $datos['nombres'];
                }
                if (empty($this->apellidos)) {
                    $this->apellidos = $datos['apellidos'];
                }
                if (empty($this->direccion) && !empty($datos['direccion'])) {
                    $this->direccion = $datos['direccion'];
                }
                if (empty($this->distrito) && !empty($datos['distrito'])) {
                    $this->distrito = $datos['distrito'];
                }
                if (empty($this->barrio) && !empty($datos['barrio'])) {
                    $this->barrio = $datos['barrio'];
                }
                
                $this->datosEncontrados = true;
                
                $fuente = $datos['fuente'] ?? 'Base de datos';
                $info_adicional = '';
                if (!empty($datos['mesa'])) {
                    $info_adicional .= " | Mesa: {$datos['mesa']}";
                }
                if (!empty($datos['local_votacion'])) {
                    $info_adicional .= " | Local: {$datos['local_votacion']}";
                }
                
                $this->mensajeBusqueda = "✅ Datos encontrados en {$fuente}{$info_adicional}";
                
                // Mostrar notificación de éxito
                session()->flash('tsje_success', 'Datos cargados automáticamente desde ' . $fuente);
                
            } else {
                $mensaje = $datos['mensaje'] ?? 'No se encontraron datos para este CI.';
                $this->mensajeBusqueda = "❌ {$mensaje} Puede completar manualmente.";
            }
            
        } catch (\Exception $e) {
            $this->mensajeBusqueda = '⚠️ Error al consultar las bases de datos. Por favor complete los datos manualmente.';
            Log::error('Error en consultarTSJE: ' . $e->getMessage());
        } finally {
            $this->buscandoDatos = false;
        }
    }
    
    public function toggleBusquedaAutomatica()
    {
        $this->busquedaAutomatica = !$this->busquedaAutomatica;
        
        if ($this->busquedaAutomatica) {
            session()->flash('message', 'Búsqueda automática activada');
        } else {
            session()->flash('message', 'Búsqueda automática desactivada');
        }
    }

    public function cargarVotante($id)
    {
        $votante = Votante::findOrFail($id);
        
        $this->votanteId = $votante->id;
        $this->ci = $votante->ci;
        $this->nombres = $votante->nombres;
        $this->apellidos = $votante->apellidos;
        $this->telefono = $votante->telefono;
        $this->email = $votante->email;
        $this->direccion = $votante->direccion;
        $this->barrio = $votante->barrio;
        $this->zona = $votante->zona;
        $this->distrito = $votante->distrito;
        $this->lider_asignado_id = $votante->lider_asignado_id;
        $this->codigo_intencion = $votante->codigo_intencion;
        $this->estado_contacto = $votante->estado_contacto;
        $this->necesita_transporte = $votante->necesita_transporte;
        $this->latitud = $votante->latitud;
        $this->longitud = $votante->longitud;
        $this->notas = $votante->notas;
        
        // Cargar campos del Excel TSJE
        $this->nro_registro = $votante->nro_registro;
        $this->codigo_departamento = $votante->codigo_departamento;
        $this->departamento = $votante->departamento;
        $this->codigo_distrito = $votante->codigo_distrito;
        $this->codigo_seccion = $votante->codigo_seccion;
        $this->seccion = $votante->seccion;
        $this->codigo_barrio = $votante->codigo_barrio;
        $this->barrio_tsje = $votante->barrio_tsje;
        $this->local_votacion = $votante->local_votacion;
        $this->descripcion_local = $votante->descripcion_local;
        $this->mesa = $votante->mesa;
        $this->orden = $votante->orden;
        $this->fecha_nacimiento = $votante->fecha_nacimiento ? $votante->fecha_nacimiento->format('Y-m-d') : null;
        $this->fecha_afiliacion = $votante->fecha_afiliacion ? $votante->fecha_afiliacion->format('Y-m-d') : null;
    }

    public function guardar()
    {
        $this->validate();

        $data = [
            'ci' => $this->ci,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'barrio' => $this->barrio,
            'zona' => $this->zona,
            'distrito' => $this->distrito,
            'lider_asignado_id' => $this->lider_asignado_id,
            'codigo_intencion' => $this->codigo_intencion,
            'estado_contacto' => $this->estado_contacto,
            'necesita_transporte' => $this->necesita_transporte,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'notas' => $this->notas,
            // Campos TSJE
            'nro_registro' => $this->nro_registro,
            'codigo_departamento' => $this->codigo_departamento,
            'departamento' => $this->departamento,
            'codigo_distrito' => $this->codigo_distrito,
            'distrito' => $this->distrito,
            'codigo_barrio' => $this->codigo_barrio,
            'barrio_tsje' => $this->barrio_tsje,
            'local_votacion' => $this->local_votacion,
            'descripcion_local' => $this->descripcion_local,
            'mesa' => $this->mesa,
            'orden' => $this->orden,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'fecha_afiliacion' => $this->fecha_afiliacion,
        ];

        if ($this->votanteId) {
            $votante = Votante::findOrFail($this->votanteId);
            $votante->update($data);
            session()->flash('message', 'Votante actualizado exitosamente.');
        } else {
            Votante::create($data);
            session()->flash('message', 'Votante creado exitosamente.');
        }

        return redirect()->route('votantes.index');
    }

    public function render()
    {
        $user = Auth::user();
        $liderIds = $user->liderIdsPropios();

        if ($liderIds === null) {
            $lideres = Lider::with('usuario')->get();
        } elseif ($liderIds->isNotEmpty()) {
            $lideres = Lider::with('usuario')->whereIn('id', $liderIds)->get();
        } else {
            $lideres = collect();
        }

        return view('livewire.votante-form', [
            'lideres' => $lideres,
        ])->layout('layouts.app');
    }
}
