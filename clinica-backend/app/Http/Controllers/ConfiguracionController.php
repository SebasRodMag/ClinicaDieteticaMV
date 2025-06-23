<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;

class ConfiguracionController extends Controller
{
    use Loggable;

    /**
     * Devuelve todas las configuraciones del sistema en formato clave-valor, convirtiendo tipos automáticamente.
     *
     * @return array
     * @throws \RuntimeException
     */
    public function obtenerConfiguraciones(): array
    {
        try {
            return Configuracion::all()->mapWithKeys(function ($item) {
                return [$item->clave => $this->procesarValor($item->valor)];
            })->toArray();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Error al procesar la configuración: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene todas las configuraciones y devuelve un mensaje de confirmación.
     *
     * @return JsonResponse
     * 
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
            $userId = Auth::id();

            $this->registrarLog($userId, 'error_configuracion', 'configuracion');
            $this->logError($userId, 'Error al obtener configuraciones', $e->getMessage());

            return response()->json([
                'message' => 'Error al obtener configuraciones',
                'detalle' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Actualiza el valor de una configuración existente por su clave.
     * Solo puede ser ejecutado por usuarios con rol 'administrador'.
     *
     * @param string $clave
     * @param Request $solicitud
     * @return JsonResponse
     */
    public function actualizarPorClave(string $clave, Request $solicitud): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('administrador')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $solicitud->validate([
            'valor' => ['required'],
        ]);

        try {
            $configuracion = Configuracion::where('clave', $clave)->firstOrFail();
            $configuracion->valor = is_array($solicitud->valor)
                ? json_encode($solicitud->valor)
                : $solicitud->valor;
            $configuracion->save();

            $this->registrarLog($user->id, 'configuracion_actualizada', "Clave: $clave");

            return response()->json(['message' => 'Configuración actualizada correctamente']);
        } catch (\Throwable $e) {
            $this->logError($user->id, 'Error actualizando configuración', $e->getMessage());

            return response()->json(['message' => 'Error al actualizar configuración'], 500);
        }
    }

    /**
     * Convierte un valor en su forma apropiada: JSON, booleano, numérico o cadena.
     *
     * @param string $valor
     * @return mixed
     */
    private function procesarValor(string $valor): mixed
    {
        $decodificado = json_decode($valor, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decodificado;
        }

        return match (strtolower($valor)) {
            'true' => true,
            'false' => false,
            default => is_numeric($valor) ? (float) $valor : $valor,
        };
    }
}
