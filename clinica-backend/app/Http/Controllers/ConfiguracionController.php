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
use OpenApi\Annotations as OA;

class ConfiguracionController extends Controller
{
    use Loggable;

    /**
     * Obtiene todas las configuraciones de la tabla `configuraciones` y las
     * devuelve como un array asociativo clave => valor, convirtiendo
     * automáticamente el valor original (string) a:
     *
     * - array u objeto, si es JSON válido
     * - booleano true/false si el valor es "true"/"false"
     * - número (float) si es numérico
     *
     * Este método es utilizado por otros controladores para obtener parámetros puntuales de la configuración.
     *
     * @return array<string, mixed>  Array asociativo de configuraciones.
     *
     * @throws \RuntimeException     Si ocurre algún error al leer o procesar las configuraciones.
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
     * Obtenemos todas las configuraciones del sistema con un mensaje de confirmación.
     *
     * RUTA:
     *  GET /obtenerConfiguraciones
     * ROLES:
     *  paciente | especialista | administrador
     *
     * RESPUESTAS:
     *  - 200 OK: Devuelve JSON con:
     *      - message: string
     *      - configuraciones: array clave => valor
     *  - 500 Error interno: Error al obtener o procesar las configuraciones.
     *
     * @OA\Get(
     *   path="/obtenerConfiguraciones",
     *   summary="Obtener configuraciones del sistema",
     *   description="Devuelve todas las configuraciones de la aplicación en formato clave-valor.",
     *   tags={"Configuración"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Configuraciones cargadas correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Configuraciones cargadas correctamente"),
     *       @OA\Property(
     *         property="configuraciones",
     *         type="object",
     *         additionalProperties=true,
     *         example={
     *           "color_tema": "#28a745",
     *           "citas_permitidas_por_dia": 10,
     *           "videollamadas_activas": true
     *         }
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener configuraciones",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al obtener configuraciones"),
     *       @OA\Property(property="detalle", type="string", nullable=true)
     *     )
     *   )
     * )
     *
     * @return JsonResponse
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
     * Actualiza el valor de una configuración existente identificada por su clave.
     *
     * RUTA:
     *  PUT /cambiarConfiguraciones/{clave}
     * ROLES:
     *  Solo usuarios con rol "administrador".
     *
     * BODY (JSON):
     *  {
     *      "valor": mixed   // Obligatorio. Puede ser string, número, booleano o array/objeto.
     *  }
     *
     * LÓGICA:
     *  - Valida que el campo "valor" venga informado.
     *  - Busca la configuración por su clave.
     *  - Si el valor es un array, se almacena como JSON.
     *  - Si el valor es escalar, se almacena tal cual (string).
     *  - Registra en logs la acción "configuracion_actualizada".
     *
     * RESPUESTAS:
     *  - 200 OK: Configuración actualizada correctamente.
     *  - 403 Forbidden: Usuario no autenticado o sin rol "administrador".
     *  - 404 Not Found: No existe configuración con la clave indicada.
     *  - 500 Error interno: Error al guardar en base de datos.
     *
     * @OA\Put(
     *   path="/cambiarConfiguraciones/{clave}",
     *   summary="Actualizar configuración por clave",
     *   description="Actualiza el valor de una configuración existente identificada por su clave.",
     *   tags={"Configuración"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="clave",
     *     in="path",
     *     required=true,
     *     description="Clave de la configuración a actualizar",
     *     @OA\Schema(type="string", example="color_tema")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Nuevo valor para la configuración",
     *     @OA\JsonContent(
     *       required={"valor"},
     *       @OA\Property(
     *         property="valor",
     *         oneOf={
     *           @OA\Schema(type="string", example="#28a745"),
     *           @OA\Schema(type="number", format="float", example=10),
     *           @OA\Schema(type="boolean", example=true),
     *           @OA\Schema(
     *             type="array",
     *             @OA\Items(type="string"),
     *             example={"mañana", "tarde"}
     *           )
     *         }
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Configuración actualizada correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Configuración actualizada correctamente")
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No autorizado")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Configuración no encontrada",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Configuracion].")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al actualizar configuración",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al actualizar configuración")
     *     )
     *   )
     * )
     *
     * @param string  $clave       Clave de la configuración a actualizar.
     * @param Request $solicitud   Petición HTTP con el campo "valor".
     *
     * @return JsonResponse
     */
    public function actualizarPorClave(string $clave, Request $solicitud): JsonResponse
    {
        $user = Auth::user();
        // Se comprueba que el usuario es administrador
        if (!$user || !$user->hasRole('administrador')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        // Se valida que venga el campo "valor"
        $solicitud->validate([
            'valor' => ['required'],
        ]);

        try {
            $configuracion = Configuracion::where('clave', $clave)->firstOrFail();
            // Sese recibe un array, se guarda como JSON
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
     * Convierte un valor de configuración (almacenado como string en BD)
     * en el tipo más adecuado:
     *
     * - Si es JSON válido, lo decodifica a array/objeto.
     * - Si es "true"/"false" (ignorando mayúsculas/minúsculas), lo convierte a booleano.
     * - Si es numérico, lo convierte a float.
     * - En cualquier otro caso, devuelve la cadena original.
     *
     * @param string $valor  Valor crudo procedente de la base de datos.
     *
     * @return mixed         Valor convertido a tipo apropiado.
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
     * Devuelve el color del tema actual como una configuración pública.
     *
     * RUTA:
     *  GET /color-tema
     * ROLES:
     *  Público (no requiere autenticación).
     *
     * La idea es que el frontend pueda obtener el color principal del sistema
     * incluso antes de que el usuario haga login.
     *
     * @OA\Get(
     *   path="/color-tema",
     *   summary="Obtener color de tema",
     *   description="Devuelve el color de tema configurado para la aplicación. Es un endpoint público.",
     *   tags={"Configuración"},
     *   @OA\Response(
     *     response=200,
     *     description="Color de tema obtenido correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="color_tema", type="string", example="#28a745")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al consultar la configuración",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Error interno al consultar la configuración."),
     *       @OA\Property(property="color_tema", type="string", example="#28a745")
     *     )
     *   )
     * )
     *
     * @return JsonResponse
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
     * Devuelve un resumen de datos para el panel de administración.
     *
     * RUTA:
     *  GET /admin/resumen-dashboard
     * ROLES:
     *  administrador
     *
     * @OA\Get(
     *   path="/admin/resumen-dashboard",
     *   summary="Resumen para panel de administración",
     *   description="Devuelve datos agregados para el dashboard del administrador (usuarios, especialistas, pacientes, citas de hoy).",
     *   tags={"Dashboard"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Resumen obtenido correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="total_usuarios", type="integer", example=42),
     *       @OA\Property(property="especialistas", type="integer", example=5),
     *       @OA\Property(property="pacientes", type="integer", example=30),
     *       @OA\Property(property="citas_hoy", type="integer", example=7)
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener datos del resumen",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al obtener datos del resumen"),
     *       @OA\Property(property="error", type="string", example="Detalle del error")
     *     )
     *   )
     * )
     *
     * @return JsonResponse
     */
    public function resumen(): JsonResponse
    {
        try {
            $totalUsuarios = User::count();
            $totalEspecialistas = Especialista::whereHas('user')->count();
            $totalPacientes = Paciente::whereHas('user')->count();
            $citasHoy = Cita::whereDate('fecha_hora_cita', Carbon::today())->count();

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
