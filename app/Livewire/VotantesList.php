<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Votante;
use App\Models\Lider;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class VotantesList extends Component
{
    use WithPagination;

    public $search = '';
    public $filtroIntencion = '';
    public $filtroEstado = '';
    public $filtroEstadoVoto = '';
    public $filtroTransporte = '';
    public $filtroPcMovil = '';
    public $filtroLider = '';
    public $filtroDistrito = '';
    public $sortBy = 'created_at';
    public $sortDir = 'desc';
    public $perPage = 50;

    public $showModal = false;
    public $editingVotante = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filtroIntencion' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDir = 'asc';
        }
    }

    public function limpiarFiltros()
    {
        $this->reset(['search', 'filtroIntencion', 'filtroEstado', 'filtroEstadoVoto', 'filtroTransporte', 'filtroPcMovil', 'filtroLider', 'filtroDistrito']);
        $this->resetPage();
    }

    public function editarVotante($id)
    {
        $this->editingVotante = $id;
        $this->showModal = true;
    }

    public function marcarVoto($id)
    {
        $user = Auth::user();
        
        // Verificar si el usuario tiene permisos para marcar votos
        if (!$user->puedeMarcarVotos()) {
            session()->flash('error', 'No tienes permisos para marcar votos.');
            return;
        }
        
        $votante = Votante::findOrFail($id);
        $votante->ya_voto = true;
        $votante->voto_registrado_en = now();
        $votante->save();

        $this->dispatch('votante-actualizado');
        session()->flash('message', 'Voto registrado exitosamente.');
    }

    public function marcarPcMovil($id)
    {
        $user = Auth::user();
        
        // Verificar si el usuario tiene permisos (mismos permisos que marcar votos)
        if (!$user->puedeMarcarVotos()) {
            session()->flash('error', 'No tienes permisos para registrar PC móvil.');
            return;
        }
        
        $votante = Votante::findOrFail($id);
        $votante->paso_por_pc_movil = !$votante->paso_por_pc_movil; // Toggle
        
        if ($votante->paso_por_pc_movil) {
            $votante->fecha_paso_pc_movil = now();
            $mensaje = 'Paso por PC móvil registrado exitosamente.';
        } else {
            $votante->fecha_paso_pc_movil = null;
            $mensaje = 'Paso por PC móvil removido exitosamente.';
        }
        
        $votante->save();

        $this->dispatch('votante-actualizado');
        session()->flash('message', $mensaje);
    }

    public function eliminarVotante($id)
    {
        Votante::findOrFail($id)->delete();
        $this->dispatch('votante-eliminado');
        session()->flash('message', 'Votante eliminado exitosamente.');
    }

    public function exportarExcel()
    {
        // Crear nueva hoja de cálculo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Configurar título del archivo
        $fechaHora = now()->format('Y-m-d_H-i-s');
        $nombreArchivo = "Votantes_Export_{$fechaHora}.xlsx";
        
        // Título del reporte
        $sheet->setCellValue('A1', 'REPORTE DE VOTANTES');
        $sheet->setCellValue('A2', 'Generado: ' . now()->format('d/m/Y H:i:s'));
        $sheet->setCellValue('A3', 'Usuario: ' . Auth::user()->name);
        
        // Encabezados de las columnas
        $encabezados = [
            'CI', 'NOMBRES', 'APELLIDOS', 'TELÉFONO', 'EMAIL', 'DIRECCIÓN', 
            'BARRIO', 'ZONA', 'DISTRITO', 'MESA/ORDEN', 'LOCAL VOTACIÓN',
            'DEPARTAMENTO', 'LÍDER ASIGNADO', 'CÓDIGO INTENCIÓN', 'ESTADO CONTACTO',
            'NECESITA TRANSPORTE', 'YA VOTÓ', 'PASÓ POR PC MÓVIL', 'FECHA NACIMIENTO', 'NOTAS'
        ];
        
        $fila = 5; // Empezar después del título
        $columna = 1;
        
        // Escribir encabezados
        $columnIndex = 0;
        foreach ($encabezados as $encabezado) {
            $cellCoordinate = Coordinate::stringFromColumnIndex($columnIndex + 1) . $fila;
            $sheet->setCellValue($cellCoordinate, $encabezado);
            $columnIndex++;
        }
        
        // Estilo para encabezados
        $sheet->getStyle("A{$fila}:T{$fila}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563eb']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        
        // Obtener datos usando la misma lógica de filtrado del método render
        $user = Auth::user();
        $query = Votante::query()->with('lider.usuario');

        // Aplicar filtros de permisos de usuario
        if ($user->esAdmin()) {
            // Los admins ven todos los votantes
        } elseif ($user->esLider() && $user->lider) {
            $query->where('lider_asignado_id', $user->lider->id);
        } elseif ($user->esVeedor()) {
            // Los veedores pueden ver todos
        } else {
            $query->whereRaw('1 = 0');
        }

        // Aplicar todos los filtros actuales
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nombres', 'like', '%' . $this->search . '%')
                    ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                    ->orWhere('ci', 'like', '%' . $this->search . '%')
                    ->orWhere('telefono', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filtroIntencion) {
            $query->where('codigo_intencion', $this->filtroIntencion);
        }

        if ($this->filtroEstado) {
            $query->where('estado_contacto', $this->filtroEstado);
        }

        if ($this->filtroEstadoVoto !== '') {
            if ($this->filtroEstadoVoto === 'votado') {
                $query->where('ya_voto', true);
            } elseif ($this->filtroEstadoVoto === 'pendiente') {
                $query->where('ya_voto', false);
            }
        }

        if ($this->filtroTransporte !== '') {
            $query->where('necesita_transporte', $this->filtroTransporte);
        }

        if ($this->filtroPcMovil !== '') {
            if ($this->filtroPcMovil === 'paso') {
                $query->where('paso_por_pc_movil', true);
            } elseif ($this->filtroPcMovil === 'no_paso') {
                $query->where('paso_por_pc_movil', false);
            }
        }

        if ($this->filtroLider) {
            $query->where('lider_asignado_id', $this->filtroLider);
        }

        if ($this->filtroDistrito) {
            $query->where('distrito', 'like', '%' . $this->filtroDistrito . '%');
        }

        // Ordenamiento
        $query->orderBy($this->sortBy, $this->sortDir);
        
        // Obtener todos los resultados (sin paginación para export)
        $votantes = $query->get();
        
        // Escribir datos
        $fila = 6;
        foreach ($votantes as $votante) {
            $mesaOrden = $votante->mesa ? "Mesa {$votante->mesa}" . ($votante->orden ? " / Orden {$votante->orden}" : '') : '-';
            $liderNombre = $votante->lider && $votante->lider->usuario ? $votante->lider->usuario->name : '-';
            $necesitaTransporte = $votante->necesita_transporte ? 'SÍ' : 'NO';
            $yaVoto = $votante->ya_voto ? 'SÍ' : 'NO';
            $pasoPorPcMovil = $votante->paso_por_pc_movil ? 'SÍ' : 'NO';
            $fechaNacimiento = $votante->fecha_nacimiento ? $votante->fecha_nacimiento->format('d/m/Y') : '-';

            $datosVotante = [
                $votante->ci,
                $votante->nombres,
                $votante->apellidos,
                $votante->telefono ?: '-',
                $votante->email ?: '-',
                $votante->direccion ?: '-',
                $votante->barrio ?: '-',
                $votante->zona ?: '-',
                $votante->distrito ?: '-',
                $mesaOrden,
                $votante->descripcion_local ?: '-',
                $votante->departamento ?: '-',
                $liderNombre,
                $votante->codigo_intencion ?: '-',
                $votante->estado_contacto ?: '-',
                $necesitaTransporte,
                $yaVoto,
                $pasoPorPcMovil,
                $fechaNacimiento,
                $votante->notas ?: '-'
            ];
            
            $columnIndex = 0;
            foreach ($datosVotante as $dato) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($columnIndex + 1) . $fila;
                $sheet->setCellValue($cellCoordinate, $dato);
                $columnIndex++;
            }
            $fila++;
        }
        
        // Ajustar ancho de columnas
        foreach (range('A', 'T') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        // Agregar información de resumen
        $respuestaFila = $fila + 2;
        $sheet->setCellValue("A{$respuestaFila}", "RESUMEN:");
        $sheet->setCellValue("A" . ($respuestaFila + 1), "Total de votantes: " . count($votantes));
        $sheet->setCellValue("A" . ($respuestaFila + 2), "Ya votaron: " . $votantes->where('ya_voto', true)->count());
        $sheet->setCellValue("A" . ($respuestaFila + 3), "Pendientes: " . $votantes->where('ya_voto', false)->count());
        
        // Crear el archivo y descargarlo
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'votantes_export') . '.xlsx';
        $writer->save($tempFile);
        
        // Retornar descarga
        return response()->download($tempFile, $nombreArchivo)->deleteFileAfterSend(true);
    }

    public function render()
    {
        $user = Auth::user();
        $query = Votante::query()->with('lider.usuario');

        // Filtrar según el rol del usuario
        if ($user->esAdmin()) {
            // Los admins ven todos los votantes sin restricciones
        } elseif ($user->esLider() && $user->lider) {
            // Los líderes solo ven sus propios votantes
            $query->where('lider_asignado_id', $user->lider->id);
        } elseif ($user->esVeedor()) {
            // Los veedores pueden ver todos los votantes pero no modificarlos
            // No aplicamos filtro adicional
        } else {
            // Si no tiene ningún rol válido, no puede ver votantes
            $query->whereRaw('1 = 0'); // Consulta que no devuelve resultados
        }

        // Búsqueda
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nombres', 'like', '%' . $this->search . '%')
                    ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                    ->orWhere('ci', 'like', '%' . $this->search . '%')
                    ->orWhere('telefono', 'like', '%' . $this->search . '%');
            });
        }

        // Filtros
        if ($this->filtroIntencion) {
            $query->where('codigo_intencion', $this->filtroIntencion);
        }

        if ($this->filtroEstado) {
            $query->where('estado_contacto', $this->filtroEstado);
        }

        if ($this->filtroEstadoVoto !== '') {
            if ($this->filtroEstadoVoto === 'votado') {
                $query->where('ya_voto', true);
            } elseif ($this->filtroEstadoVoto === 'pendiente') {
                $query->where('ya_voto', false);
            }
        }

        if ($this->filtroTransporte !== '') {
            $query->where('necesita_transporte', $this->filtroTransporte);
        }

        if ($this->filtroPcMovil !== '') {
            if ($this->filtroPcMovil === 'paso') {
                $query->where('paso_por_pc_movil', true);
            } elseif ($this->filtroPcMovil === 'no_paso') {
                $query->where('paso_por_pc_movil', false);
            }
        }

        if ($this->filtroLider) {
            $query->where('lider_asignado_id', $this->filtroLider);
        }

        if ($this->filtroDistrito) {
            $query->where('distrito', 'like', '%' . $this->filtroDistrito . '%');
        }

        // Ordenamiento
        $query->orderBy($this->sortBy, $this->sortDir);

        // Paginación
        if ($this->perPage === 'all') {
            $votantes = $query->get();
            // Convertir a formato compatible con paginación para la vista
            $votantes = new \Illuminate\Pagination\LengthAwarePaginator(
                $votantes,
                $votantes->count(),
                $votantes->count(),
                1,
                ['path' => request()->url()]
            );
        } else {
            $votantes = $query->paginate($this->perPage);
        }

        // Obtener líderes para filtro
        $lideres = Lider::with('usuario')->get();

        // Obtener distritos únicos para filtro
        $distritos = Votante::whereNotNull('distrito')
                            ->distinct()
                            ->pluck('distrito')
                            ->sort()
                            ->values();

        return view('livewire.votantes-list', [
            'votantes' => $votantes,
            'lideres' => $lideres,
            'distritos' => $distritos,
        ])->layout('layouts.app');
    }
}
