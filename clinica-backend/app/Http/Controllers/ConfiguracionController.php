<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Especialista;
use App\Models\Cita;
use Illuminate\Support\Carbon;

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

    /**
     * Retorna el color del tema como configuración pública.
     */
    public function obtenerColorTema(): JsonResponse
    {
        try {
            $color = Configuracion::get('color_tema', '#28a745');

            return response()->json([
                'success' => true,
                'color_tema' => $color,
            ]);
        } catch (\Throwable $e) {
            Log::error('[CONFIG] Error al obtener color_tema: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno al consultar la configuración.',
                'color_tema' => '#28a745'
            ], 500);
        }
    }

    /**
     * Método para enviar los datos al panel de administración con la información relevante al administrador.
     */
    public function resumen(): JsonResponse
    {
        try {
            $totalUsuarios = User::count();
            $totalEspecialistas = Especialista::whereHas('user')->count();
            $totalPacientes = Paciente::whereHas('user')->count();
            $citasHoy = Cita::whereDate('fecha', Carbon::today())->count();

            return response()->json([
                'total_usuarios' => $totalUsuarios,
                'especialistas' => $totalEspecialistas,
                'pacientes' => $totalPacientes,
                'citas_hoy' => $citasHoy,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al obtener datos del resumen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
}
