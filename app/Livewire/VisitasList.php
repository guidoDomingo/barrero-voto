<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Visita;
use App\Models\Votante;
use App\Models\Lider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VisitasList extends Component
{
    use WithPagination, WithFileUploads;

    public $busqueda = '';
    public $filtroTipo = '';
    public $filtroResultado = '';
    public $filtroLider = '';
    public $filtroFecha = '';
    public $porPagina = 25;

    // Modal para nueva visita
    public $mostrarModal = false;
    public $visitaId = null;
    public $votante_id;
    public $lider_id;
    public $fecha_visita;
    public $tipo_visita = 'Primera visita';
    public $resultado;
    public $observaciones;
    public $compromisos;
    public $proxima_visita;
    public $requiere_seguimiento = false;
    public $foto_evidencia;
    public $duracion_minutos;
    
    // Coordenadas del mapa
    public $latitud_seleccionada;
    public $longitud_seleccionada;

    protected $queryString = [
        'busqueda' => ['except' => ''],
        'filtroTipo' => ['except' => ''],
        'filtroResultado' => ['except' => ''],
    ];

    protected $rules = [
        'votante_id' => 'required|exists:votantes,id',
        'lider_id' => 'required|exists:lideres,id',
        'fecha_visita' => 'required|date',
        'tipo_visita' => 'required|in:Primera visita,Seguimiento,Convencimiento,Confirmación,Urgente',
        'resultado' => 'nullable|in:Favorable,Indeciso,No favorable,No contactado,Rechazado',
        'observaciones' => 'nullable|string|max:1000',
        'compromisos' => 'nullable|string|max:500',
        'proxima_visita' => 'nullable|date|after:fecha_visita',
        'requiere_seguimiento' => 'boolean',
        'foto_evidencia' => 'nullable|image|max:2048',
        'duracion_minutos' => 'nullable|numeric|min:0|max:999',
    ];

    public function mount()
    {
        // Pre-cargar líder en el formulario de nueva visita según rol
        $user = Auth::user();
        if ($user->esLider() && $user->lider) {
            $this->lider_id = $user->lider->id;
        } elseif ($user->esCandidato() && $user->candidato) {
            $lideres = $user->candidato->lideres;
            if ($lideres->count() === 1) {
                $this->lider_id = $lideres->first()->id;
            }
        }
    }

    public function updatingBusqueda()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset(['busqueda', 'filtroTipo', 'filtroResultado', 'filtroFecha']);
        $this->resetPage();
    }

    public function abrirModal($votanteId = null)
    {
        $this->reset(['visitaId', 'votante_id', 'fecha_visita', 'tipo_visita', 'resultado', 
                      'observaciones', 'compromisos', 'proxima_visita', 'requiere_seguimiento', 
                      'foto_evidencia', 'duracion_minutos']);
        
        $this->fecha_visita = now()->format('Y-m-d\TH:i');
        $this->tipo_visita = 'Primera visita';
        
        if ($votanteId) {
            $this->votante_id = $votanteId;
        }
        
        $user = Auth::user();
        if ($user->esLider() && $user->lider) {
            $this->lider_id = $user->lider->id;
        }
        
        $this->mostrarModal = true;
        
        // Dispatch evento para actualizar el mapa
        $this->dispatch('visitaModalOpened');
    }

    public function editarVisita($visitaId)
    {
        $visita = Visita::findOrFail($visitaId);
        
        $this->visitaId = $visita->id;
        $this->votante_id = $visita->votante_id;
        $this->lider_id = $visita->lider_id;
        $this->fecha_visita = $visita->fecha_visita->format('Y-m-d\TH:i');
        $this->tipo_visita = $visita->tipo_visita;
        $this->resultado = $visita->resultado;
        $this->observaciones = $visita->observaciones;
        $this->compromisos = $visita->compromisos;
        $this->proxima_visita = $visita->proxima_visita ? $visita->proxima_visita->format('Y-m-d\TH:i') : null;
        $this->requiere_seguimiento = $visita->requiere_seguimiento;
        $this->duracion_minutos = $visita->duracion_minutos;
        
        $this->mostrarModal = true;
        
        // Dispatch evento para actualizar el mapa
        $this->dispatch('visitaModalOpened');
    }

    public function guardarVisita()
    {
        $this->validate();

        try {
            $data = [
                'votante_id' => $this->votante_id,
                'lider_id' => $this->lider_id,
                'usuario_registro_id' => Auth::id(),
                'fecha_visita' => $this->fecha_visita,
                'tipo_visita' => $this->tipo_visita,
                'resultado' => $this->resultado,
                'observaciones' => $this->observaciones,
                'compromisos' => $this->compromisos,
                'proxima_visita' => $this->proxima_visita,
                'requiere_seguimiento' => $this->requiere_seguimiento,
                'duracion_minutos' => $this->duracion_minutos,
            ];

            if ($this->foto_evidencia) {
                $path = $this->foto_evidencia->store('visitas', 'public');
                $data['foto_evidencia'] = $path;
            }

            if ($this->visitaId) {
                $visita = Visita::findOrFail($this->visitaId);
                $visita->update($data);
                session()->flash('message', 'Visita actualizada correctamente.');
            } else {
                Visita::create($data);
                session()->flash('message', 'Visita registrada correctamente.');
            }
            
            // Actualizar coordenadas del votante si se seleccionaron nuevas
            if ($this->latitud_seleccionada && $this->longitud_seleccionada) {
                $votante = Votante::find($this->votante_id);
                if ($votante) {
                    $votante->update([
                        'latitud' => $this->latitud_seleccionada,
                        'longitud' => $this->longitud_seleccionada,
                    ]);
                }
            }

            $this->cerrarModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar visita: ' . $e->getMessage());
        }
    }

    public function eliminarVisita($visitaId)
    {
        try {
            $visita = Visita::findOrFail($visitaId);
            
            if ($visita->foto_evidencia) {
                Storage::disk('public')->delete($visita->foto_evidencia);
            }
            
            $visita->delete();
            session()->flash('message', 'Visita eliminada correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar visita: ' . $e->getMessage());
        }
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->reset(['visitaId', 'votante_id', 'fecha_visita', 'tipo_visita', 'resultado', 
                      'observaciones', 'compromisos', 'proxima_visita', 'requiere_seguimiento', 
                      'foto_evidencia', 'duracion_minutos']);
    }

    public function render()
    {
        $user = Auth::user();
        
        $query = Visita::with(['votante', 'lider.usuario', 'usuarioRegistro'])
            ->orderBy('fecha_visita', 'desc');

        // Todos los roles ven todas las visitas; filtro de líder es voluntario
        $accesoIds = $user->liderIdsParaListas();
        if ($accesoIds !== null && $accesoIds->isEmpty()) {
            $query->whereRaw('1 = 0');
        }
        if ($this->filtroLider) {
            $query->where('lider_id', $this->filtroLider);
        }

        // Búsqueda
        if ($this->busqueda) {
            $query->whereHas('votante', function($q) {
                $q->where('nombres', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('apellidos', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('ci', 'like', '%' . $this->busqueda . '%');
            });
        }

        // Filtro por tipo
        if ($this->filtroTipo) {
            $query->where('tipo_visita', $this->filtroTipo);
        }

        // Filtro por resultado
        if ($this->filtroResultado) {
            $query->where('resultado', $this->filtroResultado);
        }

        // Filtro por fecha
        if ($this->filtroFecha) {
            $query->whereDate('fecha_visita', $this->filtroFecha);
        }

        $visitas = $query->paginate($this->porPagina);
        
        $lideres = Lider::with('usuario')->get();
        $votantes = Votante::orderBy('apellidos')->get();

        return view('livewire.visitas-list', [
            'visitas' => $visitas,
            'votantes' => $votantes,
            'lideres' => $lideres,
        ])->layout('layouts.app');
    }
}
