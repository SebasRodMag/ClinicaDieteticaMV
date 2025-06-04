<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function obtenerConfiguraciones(): JsonResponse
    {
        $configuraciones = Setting::all()->mapWithKeys(function ($item) {
            $valor = json_decode($item->valor, true);

            // Si json_decode falla, mantener el valor original (string, bool, int)
            if (json_last_error() !== JSON_ERROR_NONE) {
                $valor = match (strtolower($item->valor)) {
                    'true'  => true,
                    'false' => false,
                    default => is_numeric($item->valor) ? (float) $item->valor : $item->valor,
                };
            }

            return [$item->clave => $valor];
        });

        return response()->json([
            'message' => 'Configuraciones cargadas correctamente',
            'configuraciones' => $configuraciones,
        ], 200);
    }
}