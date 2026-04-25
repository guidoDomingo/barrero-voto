<?php

namespace App\Services;

use App\Models\Votante;
use App\Models\Lider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de métricas y estadísticas
 */
class MetricsService
{
    /**
     * Obtener métricas generales de la campaña
     *
     * @return array
     */
    public function getGeneralMetrics(): array
    {
        $totalVotantes = Votante::count();
        $yaVotaron = Votante::where('ya_voto', true)->count();
        $necesitanTransporte = Votante::where('necesita_transporte', true)
            ->where('ya_voto', false)
            ->count();

        $contactados = Votante::has('contactos')->count();
        
        $porIntencion = Votante::select('codigo_intencion', DB::raw('count(*) as total'))
            ->groupBy('codigo_intencion')
            ->pluck('total', 'codigo_intencion')
            ->toArray();

        // Métricas PC Móvil
        $pasaronPorPc = Votante::where('paso_por_pc_movil', true)->count();
        $votaronSinPc = Votante::where('ya_voto', true)
            ->where('paso_por_pc_movil', false)
            ->count();
        $votaronconPc = Votante::where('ya_voto', true)
            ->where('paso_por_pc_movil', true)
            ->count();
        $noPasaronPorPc = $totalVotantes - $pasaronPorPc;

        // Votos por líder
        $votosPorLider = Lider::with('usuario', 'candidato.usuario')
            ->withCount([
                'votantes as total_votantes',
                'votantes as votantes_que_votaron' => function($query) {
                    $query->where('ya_voto', true);
                },
                'votantes as votantes_con_pc' => function($query) {
                    $query->where('paso_por_pc_movil', true);
                },
                'votantes as votos_con_pc' => function($query) {
                    $query->where('ya_voto', true)->where('paso_por_pc_movil', true);
                }
            ])
            ->orderBy('votantes_que_votaron', 'desc')
            ->get();

        $predictionService = new PredictionService();
        $prediccion = $predictionService->heuristicPrediction();

        return [
            'total_votantes' => $totalVotantes,
            'ya_votaron' => $yaVotaron,
            'pendientes_votar' => $totalVotantes - $yaVotaron,
            'porcentaje_votacion' => $totalVotantes > 0 ? round(($yaVotaron / $totalVotantes) * 100, 2) : 0,
            'contactados' => $contactados,
            'no_contactados' => $totalVotantes - $contactados,
            'porcentaje_contactados' => $totalVotantes > 0 ? round(($contactados / $totalVotantes) * 100, 2) : 0,
            'necesitan_transporte' => $necesitanTransporte,
            'por_intencion' => $porIntencion,
            'votos_estimados' => $prediccion['votos_estimados'],
            
            // Nuevas métricas PC Móvil
            'pasaron_por_pc' => $pasaronPorPc,
            'no_pasaron_por_pc' => $noPasaronPorPc,
            'porcentaje_pc' => $totalVotantes > 0 ? round(($pasaronPorPc / $totalVotantes) * 100, 2) : 0,
            'votaron_sin_pc' => $votaronSinPc,
            'votaron_con_pc' => $votaronconPc,
            'porcentaje_votos_con_pc' => $yaVotaron > 0 ? round(($votaronconPc / $yaVotaron) * 100, 2) : 0,
            'eficiencia_pc' => $pasaronPorPc > 0 ? round(($votaronconPc / $pasaronPorPc) * 100, 2) : 0,
            'votos_por_lider' => $votosPorLider,
        ];
    }

    /**
     * Obtener métricas de un líder específico
     *
     * @param int $liderId
     * @return array
     */
    public function getLeaderMetrics(int $liderId): array
    {
        $lider = Lider::with('votantes')->findOrFail($liderId);
        $votantes = $lider->votantes;

        $totalVotantes = $votantes->count();
        $yaVotaron = $votantes->where('ya_voto', true)->count();
        $necesitanTransporte = $votantes->where('necesita_transporte', true)
            ->where('ya_voto', false)
            ->count();

        $contactados = $votantes->filter(function ($v) {
            return $v->contactos()->exists();
        })->count();

        $porIntencion = $votantes->groupBy('codigo_intencion')
            ->map(fn($group) => $group->count())
            ->toArray();

        // Métricas PC Móvil para el líder
        $pasaronPorPc = $votantes->where('paso_por_pc_movil', true)->count();
        $votaronSinPc = $votantes->where('ya_voto', true)->where('paso_por_pc_movil', false)->count();
        $votaronconPc = $votantes->where('ya_voto', true)->where('paso_por_pc_movil', true)->count();

        $predictionService = new PredictionService();
        $prediccion = $predictionService->heuristicPrediction($votantes);

        return [
            'lider' => [
                'id' => $lider->id,
                'nombre' => $lider->usuario->name,
                'territorio' => $lider->territorio,
                'meta_votos' => $lider->meta_votos,
            ],
            'total_votantes' => $totalVotantes,
            'ya_votaron' => $yaVotaron,
            'pendientes_votar' => $totalVotantes - $yaVotaron,
            'porcentaje_votacion' => $totalVotantes > 0 ? round(($yaVotaron / $totalVotantes) * 100, 2) : 0,
            'contactados' => $contactados,
            'no_contactados' => $totalVotantes - $contactados,
            'porcentaje_contactados' => $totalVotantes > 0 ? round(($contactados / $totalVotantes) * 100, 2) : 0,
            'necesitan_transporte' => $necesitanTransporte,
            'por_intencion' => $porIntencion,
            'votos_estimados' => $prediccion['votos_estimados'],
            'porcentaje_meta' => $lider->meta_votos > 0
                ? round(($prediccion['votos_estimados'] / $lider->meta_votos) * 100, 2)
                : 0,
            
            // Nuevas métricas PC Móvil para líderes
            'pasaron_por_pc' => $pasaronPorPc,
            'no_pasaron_por_pc' => $totalVotantes - $pasaronPorPc,
            'porcentaje_pc' => $totalVotantes > 0 ? round(($pasaronPorPc / $totalVotantes) * 100, 2) : 0,
            'votaron_sin_pc' => $votaronSinPc,
            'votaron_con_pc' => $votaronconPc,
            'porcentaje_votos_con_pc' => $yaVotaron > 0 ? round(($votaronconPc / $yaVotaron) * 100, 2) : 0,
            'eficiencia_pc' => $pasaronPorPc > 0 ? round(($votaronconPc / $pasaronPorPc) * 100, 2) : 0,
        ];
    }

    /**
     * Calcular conversión de contactos
     *
     * @param Collection|null $votantes
     * @return array
     */
    public function getContactConversion(?Collection $votantes = null): array
    {
        if (!$votantes) {
            $votantes = Votante::with('contactos')->get();
        }

        $total = $votantes->count();
        $contactados = $votantes->filter(fn($v) => $v->contactos->isNotEmpty())->count();
        $comprometidos = $votantes->where('estado_contacto', 'Comprometido')->count();
        $votosEstimados = $votantes->sum('probabilidad_voto');

        return [
            'total_registrados' => $total,
            'contactados' => $contactados,
            'tasa_contacto' => $total > 0 ? round(($contactados / $total) * 100, 2) : 0,
            'comprometidos' => $comprometidos,
            'tasa_compromiso' => $contactados > 0 ? round(($comprometidos / $contactados) * 100, 2) : 0,
            'votos_estimados' => round($votosEstimados, 2),
            'tasa_conversion_votos' => $total > 0 ? round(($votosEstimados / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Calcular costo por voto estimado
     *
     * @return array
     */
    public function getCostPerVote(): array
    {
        $totalGastos = DB::table('gastos')
            ->where('aprobado', true)
            ->sum('monto');

        $predictionService = new PredictionService();
        $prediccion = $predictionService->heuristicPrediction();
        $votosEstimados = $prediccion['votos_estimados'];

        $costoPorVoto = $votosEstimados > 0 ? $totalGastos / $votosEstimados : 0;

        $gastosPorCategoria = DB::table('gastos')
            ->where('aprobado', true)
            ->select('categoria', DB::raw('SUM(monto) as total'))
            ->groupBy('categoria')
            ->get()
            ->pluck('total', 'categoria')
            ->toArray();

        return [
            'total_gastado' => $totalGastos,
            'votos_estimados' => round($votosEstimados, 2),
            'costo_por_voto' => round($costoPorVoto, 2),
            'gastos_por_categoria' => $gastosPorCategoria,
        ];
    }

    /**
     * Calcular ROI estimado
     *
     * @param float $valorPorVoto Valor monetario asignado a cada voto
     * @return array
     */
    public function getROI(float $valorPorVoto = 50000): array
    {
        $costData = $this->getCostPerVote();
        $totalGastado = $costData['total_gastado'];
        $votosEstimados = $costData['votos_estimados'];

        $valorTotal = $votosEstimados * $valorPorVoto;
        $roi = $totalGastado > 0 ? (($valorTotal - $totalGastado) / $totalGastado) * 100 : 0;

        return [
            'total_gastado' => $totalGastado,
            'votos_estimados' => round($votosEstimados, 2),
            'valor_por_voto' => $valorPorVoto,
            'valor_total_estimado' => round($valorTotal, 2),
            'roi_porcentaje' => round($roi, 2),
            'beneficio_neto' => round($valorTotal - $totalGastado, 2),
        ];
    }
}
