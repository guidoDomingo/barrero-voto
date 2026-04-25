<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\VoterImportService;
use App\Services\TSJEService;
use App\Models\Lider;
use Illuminate\Support\Facades\Storage;

class VotanteImporter extends Component
{
    use WithFileUploads;

    public $archivo;
    public $lider_asignado_id;
    public $actualizar_duplicados = false;
    public $consultar_tsje = true; // Nueva opción para consultar TSJE automáticamente
    public $solo_ci_importar = false; // Opción para importar solo CIs y consultar TSJE
    public $es_formato_tsje = false; // Detectar si es formato TSJE
    public $resultado = null;
    public $importando = false;

    protected $rules = [
        'archivo' => 'required|file|mimes:csv,xlsx,xls,txt|max:20480', // Aumentado a 20MB
        'lider_asignado_id' => 'nullable|exists:lideres,id', // Hacer opcional
    ];

    protected $messages = [
        'archivo.required' => 'Debe seleccionar un archivo',
        'archivo.mimes' => 'El archivo debe ser CSV, Excel (xlsx, xls) o TXT',
        'archivo.max' => 'El archivo no debe superar los 10MB',
        'lider_asignado_id.required' => 'Debe seleccionar un líder',
    ];

    public function updatedArchivo()
    {
        // Detectar si es formato TSJE cuando se suba un archivo
        $this->detectarFormatoTSJE();
    }

    private function detectarFormatoTSJE()
    {
        if (!$this->archivo) {
            return;
        }

        try {
            // Guardar archivo temporalmente para análisis
            $path = $this->archivo->store('temp');
            $fullPath = storage_path('app/' . $path);
            
            $columnas = $this->obtenerColumnasArchivo($fullPath);
            
            // Verificar si contiene columnas características del formato TSJE
            $columnasTSJE = ['nroreg', 'numero_ced', 'cod_dpto', 'desc_dep', 'mesa', 'orden'];
            $coincidencias = 0;
            
            foreach ($columnasTSJE as $columnaTSJE) {
                foreach ($columnas as $columna) {
                    if (stripos($columna, $columnaTSJE) !== false) {
                        $coincidencias++;
                        break;
                    }
                }
            }
            
            // Si encuentra 3 o más columnas características, es formato TSJE
            if ($coincidencias >= 3) {
                $this->es_formato_tsje = true;
                $this->consultar_tsje = false; // Desactivar consulta TSJE automáticamente
                $this->solo_ci_importar = false; // Desactivar solo CI
                
                session()->flash('info', 
                    'Se detectó formato de Excel del TSJE. Se desactivó automáticamente la consulta al TSJE ya que el archivo contiene datos completos.');
            } else {
                $this->es_formato_tsje = false;
            }
            
            // Limpiar archivo temporal
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
        } catch (\Exception $e) {
            // En caso de error, mantener configuración por defecto
            $this->es_formato_tsje = false;
        }
    }

    private function obtenerColumnasArchivo($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        try {
            if (in_array($extension, ['xlsx', 'xls'])) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                $primeraFila = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . '1');
                return $primeraFila[0] ?? [];
            } elseif ($extension === 'csv') {
                if (($handle = fopen($filePath, 'r')) !== false) {
                    $columnas = fgetcsv($handle, 1000, ',');
                    fclose($handle);
                    return $columnas ?: [];
                }
            }
        } catch (\Exception $e) {
            return [];
        }
        
        return [];
    }

    public function importar()
    {
        $this->validate();

        // Resetear resultado previo
        $this->resultado = null;

        try {
            // Guardar archivo temporalmente
            $path = $this->archivo->store('temp');
            $fullPath = Storage::path($path);

            // Importar con opciones mejoradas
            $importService = new VoterImportService();
            $this->resultado = $importService->importar(
                $fullPath,
                $this->lider_asignado_id,
                auth()->id(), // ID del usuario autenticado
                $this->actualizar_duplicados
            );

            // Limpiar archivo temporal
            Storage::delete($path);

            session()->flash('message', 
                'Importación completada. ' . 
                ($this->resultado['nuevos'] ?? 0) . ' votantes procesados exitosamente' .
                (($this->resultado['fallidos'] ?? 0) > 0 ? ', ' . ($this->resultado['fallidos'] ?? 0) . ' errores.' : '.')
            );

        } catch (\Exception $e) {
            $this->resultado = [
                'exito' => false,
                'error' => 'Error general: ' . $e->getMessage()
            ];
        }
    }

    public function descargarPlantilla()
    {
        // Crear contenido XML para Excel (formato SpreadsheetML)
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Worksheet ss:Name="Plantilla Votantes TSJE">
  <Table>
   <Row>
    <Cell><Data ss:Type="String">nroreg</Data></Cell>
    <Cell><Data ss:Type="String">cod_dpto</Data></Cell>
    <Cell><Data ss:Type="String">desc_dep</Data></Cell>
    <Cell><Data ss:Type="String">cod_dist</Data></Cell>
    <Cell><Data ss:Type="String">desc_dis</Data></Cell>
    <Cell><Data ss:Type="String">codigo_sec</Data></Cell>
    <Cell><Data ss:Type="String">desc_sec</Data></Cell>
    <Cell><Data ss:Type="String">slocal</Data></Cell>
    <Cell><Data ss:Type="String">desc_locanr</Data></Cell>
    <Cell><Data ss:Type="String">mesa</Data></Cell>
    <Cell><Data ss:Type="String">orden</Data></Cell>
    <Cell><Data ss:Type="String">numero_ced</Data></Cell>
    <Cell><Data ss:Type="String">apellido</Data></Cell>
    <Cell><Data ss:Type="String">nombre</Data></Cell>
    <Cell><Data ss:Type="String">fecha_naci</Data></Cell>
    <Cell><Data ss:Type="String">direccion</Data></Cell>
    <Cell><Data ss:Type="String">fecha_afil</Data></Cell>
    <Cell><Data ss:Type="String">Teléfono</Data></Cell>
    <Cell><Data ss:Type="String">Email</Data></Cell>
    <Cell><Data ss:Type="String">Intención (A/B/C/D/E)</Data></Cell>
    <Cell><Data ss:Type="String">Necesita Transporte (SI/NO)</Data></Cell>
    <Cell><Data ss:Type="String">Notas</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">228</Data></Cell>
    <Cell><Data ss:Type="String">3</Data></Cell>
    <Cell><Data ss:Type="String">CORDILLERA</Data></Cell>
    <Cell><Data ss:Type="String">29</Data></Cell>
    <Cell><Data ss:Type="String">SAN BERNARDINO</Data></Cell>
    <Cell><Data ss:Type="String">228</Data></Cell>
    <Cell><Data ss:Type="String">SAN BERNARDINO</Data></Cell>
    <Cell><Data ss:Type="String">1</Data></Cell>
    <Cell><Data ss:Type="String">ESCUELA N 3046</Data></Cell>
    <Cell><Data ss:Type="String">1</Data></Cell>
    <Cell><Data ss:Type="String">1</Data></Cell>
    <Cell><Data ss:Type="String">8454836</Data></Cell>
    <Cell><Data ss:Type="String">ACOSTA</Data></Cell>
    <Cell><Data ss:Type="String">JUAN</Data></Cell>
    <Cell><Data ss:Type="String">26/12/1980</Data></Cell>
    <Cell><Data ss:Type="String">B° CENTRO</Data></Cell>
    <Cell><Data ss:Type="String">23/12/2019</Data></Cell>
    <Cell><Data ss:Type="String">0981234567</Data></Cell>
    <Cell><Data ss:Type="String">juan@email.com</Data></Cell>
    <Cell><Data ss:Type="String">A</Data></Cell>
    <Cell><Data ss:Type="String">NO</Data></Cell>
    <Cell><Data ss:Type="String">Contactar por la mañana</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String"></Data></Cell>
    <Cell><Data ss:Type="String">INSTRUCCIONES PARA EXCEL TSJE:</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String"></Data></Cell>
    <Cell><Data ss:Type="String">1. Use exactamente estos nombres de columnas</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String"></Data></Cell>
    <Cell><Data ss:Type="String">2. Los datos de TSJE se importan automáticamente</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String"></Data></Cell>
    <Cell><Data ss:Type="String">3. Puede agregar teléfono, email, intención y notas</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String"></Data></Cell>
    <Cell><Data ss:Type="String">4. Intención: A=Alto, B=Medio, C=Bajo, D=Crítico, E=Indeciso</Data></Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>';

        return response($xml)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="plantilla_votantes_tsje_formato.xls"');
    }

    public function limpiar()
    {
        $this->reset(['archivo', 'resultado', 'actualizar_duplicados', 'consultar_tsje', 'solo_ci_importar', 'es_formato_tsje']);
        $this->consultar_tsje = true; // Resetear a valor por defecto
    }

    public function render()
    {
        $user = auth()->user();
        $liderIds = $user->liderIdsPropios();

        if ($liderIds === null) {
            $lideres = Lider::with('usuario')->get();
        } elseif ($liderIds->isNotEmpty()) {
            $lideres = Lider::with('usuario')->whereIn('id', $liderIds)->get();
        } else {
            $lideres = collect();
        }

        return view('livewire.votante-importer', [
            'lideres' => $lideres,
        ])->layout('layouts.app');
    }
}
