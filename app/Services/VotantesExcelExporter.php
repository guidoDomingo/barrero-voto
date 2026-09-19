<?php

namespace App\Services;

use App\Models\Votante;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VotantesExcelExporter
{
    private const HEADERS = [
        'ID', 'Estado del registro', 'CI', 'Nro. registro', 'Nombres', 'Apellidos',
        'Teléfono', 'Email', 'Fecha de nacimiento', 'Fecha de afiliación', 'Género',
        'Ocupación', 'Dirección', 'Barrio', 'Zona', 'Distrito', 'Cód. departamento',
        'Departamento', 'Cód. distrito', 'Cód. sección', 'Sección', 'Local de votación',
        'Descripción del local', 'Mesa', 'Orden', 'Latitud', 'Longitud',
        'Intención registrada (no es voto real)', 'Estado de contacto', 'Ya votó', 'Fecha de voto',
        'Pasó por PC móvil', 'Fecha de paso por PC', 'Necesita transporte', 'Notas',
        'ID líder', 'Líder asignado', 'Candidato', 'Creado por', 'Actualizado por',
        'Fecha de creación', 'Última actualización', 'Fecha de eliminación',
    ];

    public function download(string $usuario): BinaryFileResponse
    {
        // PhpSpreadsheet conserva las celdas en memoria mientras arma el XLSX.
        // El respaldo completo necesita más que el límite habitual de PHP (128 MB).
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $votantes = Votante::withTrashed()
            ->with(['lider.usuario', 'lider.candidato.usuario', 'creadoPor', 'actualizadoPor'])
            ->orderBy('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $this->buildSummarySheet($spreadsheet->getActiveSheet(), $votantes, $usuario);
        $this->buildVotersSheet($spreadsheet->createSheet(), $votantes);
        $this->buildVotedSheet($spreadsheet->createSheet(), $votantes->where('ya_voto', true));

        $spreadsheet->setActiveSheetIndex(0);
        $fileName = 'Historico_votantes_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('historico_votantes_', true) . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    private function buildSummarySheet(Worksheet $sheet, Collection $votantes, string $usuario): void
    {
        $sheet->setTitle('Resumen');
        $sheet->fromArray([
            ['RESPALDO HISTÓRICO DE VOTANTES'],
            ['Generado', now()->format('d/m/Y H:i:s')],
            ['Generado por', $usuario],
            ['Total de registros', $votantes->count()],
            ['Registros activos', $votantes->whereNull('deleted_at')->count()],
            ['Registros eliminados', $votantes->whereNotNull('deleted_at')->count()],
            ['Ya votaron', $votantes->where('ya_voto', true)->count()],
            ['Pasaron por PC móvil', $votantes->where('paso_por_pc_movil', true)->count()],
            ['Necesitan transporte', $votantes->where('necesita_transporte', true)->count()],
            ['Importante', 'La intención registrada y el candidato/equipo asignado no indican por quién votó realmente la persona.'],
        ], null, 'A1');

        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->applyFromArray($this->titleStyle());
        $sheet->getStyle('A2:A10')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(85);
        $sheet->getStyle('B10')->getAlignment()->setWrapText(true);
    }

    private function buildVotersSheet(Worksheet $sheet, Collection $votantes): void
    {
        $sheet->setTitle('Votantes');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        foreach ($votantes as $index => $votante) {
            $row = $index + 2;
            $lider = $votante->lider;
            $values = [
                $votante->id,
                $votante->trashed() ? 'Eliminado' : 'Activo',
                $votante->ci,
                $votante->nro_registro,
                $votante->nombres,
                $votante->apellidos,
                $votante->telefono,
                $votante->email,
                $this->date($votante->fecha_nacimiento),
                $this->date($votante->fecha_afiliacion),
                $votante->genero,
                $votante->ocupacion,
                $votante->direccion,
                $votante->barrio,
                $votante->zona,
                $votante->distrito,
                $votante->codigo_departamento,
                $votante->departamento,
                $votante->codigo_distrito,
                $votante->codigo_seccion,
                $votante->seccion,
                $votante->local_votacion,
                $votante->descripcion_local,
                $votante->mesa,
                $votante->orden,
                $votante->latitud,
                $votante->longitud,
                $votante->codigo_intencion,
                $votante->estado_contacto,
                $this->yesNo($votante->ya_voto),
                $this->dateTime($votante->voto_registrado_en),
                $this->yesNo($votante->paso_por_pc_movil),
                $this->dateTime($votante->fecha_paso_pc_movil),
                $this->yesNo($votante->necesita_transporte),
                $votante->notas,
                $votante->lider_asignado_id,
                $lider?->usuario?->name,
                $lider?->candidato?->usuario?->name,
                $votante->creadoPor?->name,
                $votante->actualizadoPor?->name,
                $this->dateTime($votante->created_at),
                $this->dateTime($votante->updated_at),
                $this->dateTime($votante->deleted_at),
            ];

            $sheet->fromArray($values, null, "A{$row}");

            // Excel no debe convertir identificadores ni teléfonos en números.
            foreach (['C', 'D', 'G', 'Q', 'S', 'T', 'X'] as $column) {
                $sheet->setCellValueExplicit("{$column}{$row}", (string) ($sheet->getCell("{$column}{$row}")->getValue() ?? ''), DataType::TYPE_STRING);
            }
        }

        $lastRow = max(2, $votantes->count() + 1);
        $lastColumn = 'AQ';
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray($this->headerStyle());
        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('D1D5DB');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");

        foreach (range(1, count(self::HEADERS)) as $columnIndex) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        foreach (['M', 'W', 'AI'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(false)->setWidth(45);
            $sheet->getStyle("{$column}2:{$column}{$lastRow}")->getAlignment()->setWrapText(true);
        }
    }

    private function buildVotedSheet(Worksheet $sheet, Collection $votantes): void
    {
        $headers = [
            'CI', 'Nombres', 'Apellidos', 'Fecha registrada de voto',
            'Intención registrada (no es voto real)', 'Líder asignado',
            'Candidato/equipo asignado', 'Estado del registro',
        ];

        $sheet->setTitle('Personas que votaron');
        $sheet->fromArray($headers, null, 'A1');

        foreach ($votantes->values() as $index => $votante) {
            $row = $index + 2;
            $lider = $votante->lider;

            $sheet->fromArray([
                $votante->ci,
                $votante->nombres,
                $votante->apellidos,
                $this->dateTime($votante->voto_registrado_en),
                $votante->codigo_intencion,
                $lider?->usuario?->name,
                $lider?->candidato?->usuario?->name,
                $votante->trashed() ? 'Eliminado' : 'Activo',
            ], null, "A{$row}");

            $sheet->setCellValueExplicit("A{$row}", (string) ($votante->ci ?? ''), DataType::TYPE_STRING);
        }

        $lastRow = max(2, $votantes->count() + 1);
        $sheet->getStyle('A1:H1')->applyFromArray($this->headerStyle());
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:H{$lastRow}");

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function date($value): string
    {
        return $value?->format('d/m/Y') ?? '';
    }

    private function dateTime($value): string
    {
        return $value?->format('d/m/Y H:i:s') ?? '';
    }

    private function yesNo(bool $value): string
    {
        return $value ? 'Sí' : 'No';
    }

    private function titleStyle(): array
    {
        return [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    private function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
    }
}
