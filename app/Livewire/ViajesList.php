<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Viaje;
use App\Models\Lider;
use Illuminate\Support\Facades\Auth;

class ViajesList extends Component
{
    use WithPagination;

    public $busqueda = '';
    public $filtroEstado = '';
    public $filtroLider = '';
    public $filtroFecha = '';
    public $porPagina = 25;

    public $viajeSeleccionado = null;
    public $mostrarModal = false;

    protected $queryString = [
        'busqueda' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
        'filtroLider' => ['except' => ''],
        'filtroFecha' => ['except' => ''],
    ];

    public function updatingBusqueda()
    {
        $this->resetPage();
    }

    public function updatingFiltroEstado()
    {
        $this->resetPage();
    }

    public function updatingFiltroLider()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->reset(['busqueda', 'filtroEstado', 'filtroLider', 'filtroFecha']);
        $this->resetPage();
    }

    public function verDetalles($viajeId)
    {
        $this->viajeSeleccionado = Viaje::with(['vehiculo', 'chofer', 'liderResponsable.usuario', 'votantes'])
            ->findOrFail($viajeId);
        $this->mostrarModal = true;
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->viajeSeleccionado = null;
    }

    public function cambiarEstado($viajeId, $nuevoEstado)
    {
        try {
            $viaje = Viaje::findOrFail($viajeId);
            $viaje->estado = $nuevoEstado;
            $viaje->save();

            session()->flash('message', 'Estado del viaje actualizado correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar estado: ' . $e->getMessage());
        }
    }

    public function marcarCompletado($viajeId)
    {
        $this->cambiarEstado($viajeId, 'Completado');
        $this->cerrarModal();
    }

    public function cancelarViaje($viajeId)
    {
        $this->cambiarEstado($viajeId, 'Cancelado');
        $this->cerrarModal();
    }

    public function eliminarViaje($viajeId)
    {
        try {
            $viaje = Viaje::findOrFail($viajeId);
            
            // Solo permitir eliminar si está en estado Planificado
            if ($viaje->estado !== 'Planificado') {
                session()->flash('error', 'Solo se pueden eliminar viajes en estado Planificado.');
                return;
            }

            $viaje->votantes()->detach();
            $viaje->delete();

            session()->flash('message', 'Viaje eliminado correctamente.');
            $this->cerrarModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar viaje: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::user();
        
        $query = Viaje::with(['vehiculo', 'chofer', 'liderResponsable.usuario', 'votantes'])
            ->orderBy('fecha_viaje', 'desc')
            ->orderBy('hora_salida', 'desc');

        // Todos los roles ven todos los viajes; filtro de líder es voluntario
        $accesoIds = $user->liderIdsParaListas();
        if ($accesoIds !== null && $accesoIds->isEmpty()) {
            $query->whereRaw('1 = 0');
        }
        if ($this->filtroLider) {
            $query->where('lider_responsable_id', $this->filtroLider);
        }

        // Búsqueda
        if ($this->busqueda) {
            $query->where(function($q) {
                $q->where('punto_partida', 'like', '%' . $this->busqueda . '%')
                  ->orWhere('destino', 'like', '%' . $this->busqueda . '%')
                  ->orWhereHas('chofer', function($sq) {
                      $sq->where('nombre_completo', 'like', '%' . $this->busqueda . '%');
                  });
            });
        }

        // Filtro por estado
        if ($this->filtroEstado) {
            $query->where('estado', $this->filtroEstado);
        }

        // Filtro por fecha
        if ($this->filtroFecha) {
            $query->whereDate('fecha_viaje', $this->filtroFecha);
        }

        $viajes = $query->paginate($this->porPagina);

        $lideres = Lider::with('usuario')->get();

        return view('livewire.viajes-list', [
            'viajes' => $viajes,
            'lideres' => $lideres,
        ])->layout('layouts.app');
    }
}
