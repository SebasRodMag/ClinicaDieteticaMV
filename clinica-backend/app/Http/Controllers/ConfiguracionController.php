<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\Loggable;

class ConfiguracionController extends Controller
{
    use Loggable;
    public function obtenerConfiguraciones(): array
    {
        try {
            return Configuracion::all()->mapWithKeys(function ($item) {
                $valor = json_decode($item->valor, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $valor = match (strtolower($item->valor)) {
                        'true' => true,
                        'false' => false,
                        default => is_numeric($item->valor) ? (float) $item->valor : $item->valor,
                    };
                }

                return [$item->clave => $valor];
            })->toArray();
        } catch (\Throwable $e) {
            // Lo re-lanzamos para que el controlador superior lo capture
            throw new \RuntimeException('Error al procesar la configuración: ' . $e->getMessage(), 0, $e);
        }
    }


    /**
     * Esta Método, llamam al primero para obtener la configuración y luego la actualiza.
     * La unica diferencia es que este envia un mensaje de configrmacion
     * @return JsonResponse|mixed
     */
    public function obtenerConfiguracionesConMensaje(): JsonResponse
    {
        try {
            $configuraciones = $this->obtenerConfiguraciones();

            return response()->json([
                'message' => 'Configuraciones cargadas correctamente',
                'configuraciones' => $configuraciones,
            ], 200);
        } catch (\Throwable $e) {
            $userId = auth()->id();

            // Registrar en tabla `logs`
            $this->registrarLog($userId, 'error_configuracion', 'configuracion');

            // Registrar en el log del sistema con contexto
            $this->logError($userId, 'Error al obtener configuraciones', $e->getMessage());

            return response()->json([
                'message' => 'Error al obtener configuraciones',
                'detalle' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function actualizarPorClave($clave, Request $request)
    {
        $config = Configuracion::where('clave', $clave)->firstOrFail();
        $config->valor = is_array($request->valor) ? json_encode($request->valor) : $request->valor;
        $config->save();

        return response()->json(['message' => 'Configuración actualizada correctamente']);
    }
}