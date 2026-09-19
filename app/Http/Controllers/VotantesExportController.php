<?php

namespace App\Http\Controllers;

use App\Services\VotantesExcelExporter;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VotantesExportController extends Controller
{
    public function historico(VotantesExcelExporter $exporter): BinaryFileResponse
    {
        return $exporter->download(Auth::user()->name);
    }
}
