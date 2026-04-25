<?php

namespace App\Services;

use App\Models\Votante;
use App\Models\Lider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Servicio de importación masiva de votantes
 */
class VoterImportService
{
    private array $errores = [];
    private array $advertencias = [];
    private int $nuevos = 0;
    private int $actualizados = 0;
    private int $duplicados = 0;
    private int $fallidos = 0;

    /**
     * Importar votantes desde archivo CSV o XLSX
     *
     * @param string $filePath
     * @param int $liderAsignadoId
     * @param int $usuarioId
     * @param bool $actualizarDuplicados
     * @return array
     */
    public function importar(
        string $filePath,
        ?int $liderAsignadoId,
        int $usuarioId,
        bool $actualizarDuplicados = false
    ): array {
        $this->resetContadores();

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        try {
            if (in_array($extension, ['xlsx', 'xls'])) {
                $datos = $this->leerExcel($filePath);
            } elseif ($extension === 'csv') {
                $datos = $this->leerCSV($filePath);
            } else {
                return [
                    'exito' => false,
                    'error' => 'Formato de archivo no soportado. Use CSV, XLS o XLSX.',
                ];
            }

            $this->procesarDatos($datos, $liderAsignadoId, $usuarioId, $actualizarDuplicados);

            return [
                'exito' => true,
                'total_procesados' => count($datos),
                'nuevos' => $this->nuevos,
                'actualizados' => $this->actualizados,
                'duplicados' => $this->duplicados,
                'fallidos' => $this->fallidos,
                'errores' => $this->errores,
                'advertencias' => $this->advertencias,
            ];
        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => 'Error al procesar archivo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Leer archivo CSV
     *
     * @param string $filePath
     * @return array
     */
    private function leerCSV(string $filePath): array
    {
        $datos = [];
        $encabezados = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $primeraFila = true;

            while (($fila = fgetcsv($handle, 1000, ',')) !== false) {
                if ($primeraFila) {
                    $encabezados = array_map('trim', $fila);
                    $primeraFila = false;
                    continue;
                }

                if (count($fila) === count($encabezados)) {
                    $datos[] = array_combine($encabezados, $fila);
                }
            }

            fclose($handle);
        }

        return $datos;
    }

    /**
     * Leer archivo Excel
     *
     * @param string $filePath
     * @return array
     */
    private function leerExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $filas = $worksheet->toArray();

        if (empty($filas)) {
            return [];
        }

        $encabezados = array_map('trim', array_shift($filas));
        $datos = [];

        foreach ($filas as $fila) {
            if (count($fila) >= count($encabezados)) {
                // Tomar solo las columnas necesarias para evitar problemas con columnas extra
                $filaRecortada = array_slice($fila, 0, count($encabezados));
                $datos[] = array_combine($encabezados, $filaRecortada);
            }
        }

        return $datos;
    }

    /**
     * Procesar datos y crear/actualizar votantes
     *
     * @param array $datos
     * @param int $liderAsignadoId
     * @param int $usuarioId
     * @param bool $actualizarDuplicados
     */
    private function procesarDatos(
        array $datos,
        ?int $liderAsignadoId,
        int $usuarioId,
        bool $actualizarDuplicados
    ): void {
        foreach ($datos as $index => $fila) {
            $numeroFila = $index + 2; // +2 porque empieza en 1 y saltamos encabezado

            try {
                $datosVotante = $this->mapearDatos($fila);
                
                // Validar datos
                $validacion = $this->validarDatos($datosVotante, $numeroFila);
                
                if (!$validacion['valido']) {
                    $this->fallidos++;
                    continue;
                }

                // Usar los datos potencialmente modificados por la validación
                $datosVotante = $validacion['datos'];

                // Verificar duplicados por CI o teléfono
                $existente = $this->buscarDuplicado($datosVotante);

                if ($existente) {
                    if ($actualizarDuplicados) {
                        $this->actualizarVotante($existente, $datosVotante, $usuarioId);
                        $this->actualizados++;
                    } else {
                        $this->duplicados++;
                        $this->advertencias[] = [
                            'fila' => $numeroFila,
                            'mensaje' => "Votante duplicado (CI: {$datosVotante['ci']})",
                        ];
                    }
                } else {
                    $this->crearVotante($datosVotante, $liderAsignadoId, $usuarioId);
                    $this->nuevos++;
                }
            } catch (\Exception $e) {
                $this->fallidos++;
                $this->errores[] = [
                    'fila' => $numeroFila,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Mapear datos del archivo a campos del modelo
     *
     * @param array $fila
     * @return array
     */
    private function mapearDatos(array $fila): array
    {
        // Normalizar las claves del array para manejar diferentes formatos
        $filaNormalizada = [];
        foreach ($fila as $clave => $valor) {
            $claveNormalizada = strtolower(trim($clave));
            $filaNormalizada[$claveNormalizada] = $valor;
        }

        return [
            // Mapeo para Excel TSJE
            'ci' => $filaNormalizada['numero_ced'] ?? $filaNormalizada['cedula'] ?? $filaNormalizada['ci'] ?? null,
            'nombres' => $filaNormalizada['nombre'] ?? $filaNormalizada['nombres'] ?? null,
            'apellidos' => $filaNormalizada['apellido'] ?? $filaNormalizada['apellidos'] ?? null,
            'direccion' => $filaNormalizada['direccion'] ?? null,
            'fecha_nacimiento' => $this->parsearFecha($filaNormalizada['fecha_naci'] ?? $filaNormalizada['fecha_nacimiento'] ?? $filaNormalizada['nacimiento'] ?? null),
            
            // Campos adicionales del Excel TSJE
            'nro_registro' => $filaNormalizada['nroreg'] ?? null,
            'codigo_departamento' => $filaNormalizada['cod_dpto'] ?? null,
            'departamento' => $filaNormalizada['desc_dep'] ?? null,
            'codigo_distrito' => $filaNormalizada['cod_dist'] ?? null,
            'distrito_tsje' => $filaNormalizada['desc_dis'] ?? $filaNormalizada['distrito'] ?? null,
            'codigo_seccion' => $filaNormalizada['codigo_sec'] ?? null,
            'seccion' => $filaNormalizada['desc_sec'] ?? null,
            'codigo_barrio' => $filaNormalizada['codigo_sec'] ?? null,
            'barrio_tsje' => $filaNormalizada['desc_sec'] ?? null,
            'local_votacion' => $filaNormalizada['slocal'] ?? null,
            'descripcion_local' => $filaNormalizada['desc_locanr'] ?? null,
            'mesa' => $filaNormalizada['mesa'] ?? null,
            'orden' => $filaNormalizada['orden'] ?? null,
            'fecha_afiliacion' => $this->parsearFecha($filaNormalizada['fecha_afil'] ?? null),
            
            // Campos estándar (compatibilidad con otros formatos)
            'telefono' => $filaNormalizada['telefono'] ?? $filaNormalizada['tel'] ?? $filaNormalizada['celular'] ?? null,
            'email' => $filaNormalizada['email'] ?? $filaNormalizada['correo'] ?? null,
            'barrio' => $filaNormalizada['barrio'] ?? null,
            'zona' => $filaNormalizada['zona'] ?? null,
            'genero' => $filaNormalizada['genero'] ?? $filaNormalizada['sexo'] ?? null,
            'ocupacion' => $filaNormalizada['ocupacion'] ?? null,
            'codigo_intencion' => strtoupper($filaNormalizada['codigo_intencion'] ?? $filaNormalizada['intencion'] ?? 'C'),
            'necesita_transporte' => $this->parsearBooleano($filaNormalizada['necesita_transporte'] ?? $filaNormalizada['transporte'] ?? false),
            'notas' => $filaNormalizada['notas'] ?? $filaNormalizada['observaciones'] ?? null,
            'latitud' => $filaNormalizada['latitud'] ?? $filaNormalizada['lat'] ?? null,
            'longitud' => $filaNormalizada['longitud'] ?? $filaNormalizada['lon'] ?? $filaNormalizada['lng'] ?? null,
        ];
    }

    /**
     * Validar datos del votante
     *
     * @param array $datos
     * @param int $numeroFila
     * @return array
     */
    private function validarDatos(array $datos, int $numeroFila): array
    {
        // Para archivos TSJE, el CI es obligatorio pero nombres/apellidos pueden estar vacios inicialmente
        $esFormatoTSJE = !empty($datos['nro_registro']) || !empty($datos['mesa']) || !empty($datos['orden']);
        
        if ($esFormatoTSJE) {
            $reglas = [
                'ci' => 'required|string|max:20',
                'nombres' => 'nullable|string|max:100',
                'apellidos' => 'nullable|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'codigo_intencion' => 'nullable|in:A,B,C,D,E',
            ];
            
            // Si nombres o apellidos están vacíos, asignar valores por defecto
            if (empty($datos['nombres'])) {
                $datos['nombres'] = 'PENDIENTE';
            }
            if (empty($datos['apellidos'])) {
                $datos['apellidos'] = 'PENDIENTE';
            }
        } else {
            $reglas = [
                'ci' => 'nullable|string|max:20',
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'codigo_intencion' => 'nullable|in:A,B,C,D,E',
            ];
        }

        $validator = Validator::make($datos, $reglas);

        if ($validator->fails()) {
            $this->errores[] = [
                'fila' => $numeroFila,
                'errores' => $validator->errors()->all(),
            ];

            return ['valido' => false, 'datos' => $datos];
        }

        return ['valido' => true, 'datos' => $datos];
    }

    /**
     * Buscar votante duplicado por CI o teléfono
     *
     * @param array $datos
     * @return Votante|null
     */
    private function buscarDuplicado(array $datos): ?Votante
    {
        if ($datos['ci']) {
            $votante = Votante::where('ci', $datos['ci'])->first();
            if ($votante) {
                return $votante;
            }
        }

        if ($datos['telefono']) {
            return Votante::where('telefono', $datos['telefono'])->first();
        }

        return null;
    }

    /**
     * Crear nuevo votante
     *
     * @param array $datos
     * @param int $liderAsignadoId
     * @param int $usuarioId
     */
    private function crearVotante(array $datos, ?int $liderAsignadoId, int $usuarioId): void
    {
        $datos['lider_asignado_id'] = $liderAsignadoId;
        $datos['creado_por_usuario_id'] = $usuarioId;
        $datos['actualizado_por_usuario_id'] = $usuarioId;
        $datos['estado_contacto'] = 'Nuevo';

        Votante::create($datos);
    }

    /**
     * Actualizar votante existente
     *
     * @param Votante $votante
     * @param array $datos
     * @param int $usuarioId
     */
    private function actualizarVotante(Votante $votante, array $datos, int $usuarioId): void
    {
        $datos['actualizado_por_usuario_id'] = $usuarioId;
        unset($datos['ci']); // No actualizar CI

        $votante->update($datos);
    }

    /**
     * Parsear valor booleano desde string
     *
     * @param mixed $valor
     * @return bool
     */
    private function parsearBooleano($valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        $valor = strtolower(trim($valor));
        return in_array($valor, ['si', 'sí', 'yes', 'true', '1', 'verdadero']);
    }

    /**
     * Parsear fecha desde diferentes formatos
     *
     * @param mixed $valor
     * @return string|null
     */
    private function parsearFecha($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        // Si ya es una fecha válida, devolverla
        if ($valor instanceof \DateTime) {
            return $valor->format('Y-m-d');
        }

        $valor = trim($valor);
        
        // Intentar diferentes formatos comunes
        $formatos = [
            'd/m/Y',      // 26/12/1980
            'd-m-Y',      // 26-12-1980
            'Y-m-d',      // 1980-12-26
            'd/m/y',      // 26/12/80
            'd-m-y',      // 26-12-80
            'm/d/Y',      // 12/26/1980
            'Y/m/d',      // 1980/12/26
        ];

        foreach ($formatos as $formato) {
            $fecha = \DateTime::createFromFormat($formato, $valor);
            if ($fecha !== false) {
                return $fecha->format('Y-m-d');
            }
        }

        // Intentar con strtotime como último recurso
        $timestamp = strtotime($valor);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Resetear contadores
     */
    private function resetContadores(): void
    {
        $this->errores = [];
        $this->advertencias = [];
        $this->nuevos = 0;
        $this->actualizados = 0;
        $this->duplicados = 0;
        $this->fallidos = 0;
    }

    /**
     * Generar plantilla CSV de ejemplo
     *
     * @return string Contenido del CSV
     */
    public static function generarPlantillaCSV(): string
    {
        $encabezados = [
            'ci',
            'nombres',
            'apellidos',
            'telefono',
            'email',
            'fecha_nacimiento',
            'genero',
            'ocupacion',
            'direccion',
            'barrio',
            'zona',
            'distrito',
            'latitud',
            'longitud',
            'codigo_intencion',
            'necesita_transporte',
            'notas',
        ];

        $ejemplo = [
            '1234567',
            'Juan',
            'Pérez García',
            '0981-123456',
            'juan@email.com',
            '1985-05-15',
            'M',
            'Comerciante',
            'Av. Principal 123',
            'Centro',
            'Zona 1',
            'Distrito 1',
            '-25.2867',
            '-57.6333',
            'A',
            'Si',
            'Votante comprometido',
        ];

        $csv = implode(',', $encabezados) . "\n";
        $csv .= implode(',', $ejemplo) . "\n";

        return $csv;
    }
}
