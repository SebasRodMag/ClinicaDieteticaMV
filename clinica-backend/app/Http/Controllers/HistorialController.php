<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class HistorialController extends Controller
{
    use Loggable;

    /**
     * Listar entradas del historial de un especialista logueado.
     *
     * Devuelve todas las entradas de historiales de los pacientes del especialista autenticado.
     * Incluye relaciones con paciente (y su usuario) y con la cita.
     *
     * RUTA:
     *  GET /historial-paciente/
     * ROLES:
     *  especialista
     *
     * @OA\Get(
     *   path="/historial-paciente/",
     *   summary="Listar historiales de pacientes del especialista",
     *   description="Devuelve las entradas de historial asociadas a los pacientes del especialista autenticado.",
     *   tags={"Historial"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Historiales obtenidos correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Historiales obtenidos correctamente"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           example={
     *             "id": 10,
     *             "id_paciente": 5,
     *             "id_especialista": 2,
     *             "fecha": "2025-05-01",
     *             "comentarios_paciente": "Me noto con más energía.",
     *             "observaciones_especialista": "Buena adherencia a la dieta.",
     *             "recomendaciones": "Mantener plan actual.",
     *             "dieta": "Plan hipocalórico",
     *             "lista_compra": "Verduras, frutas, legumbres",
     *             "paciente": {
     *               "id": 5,
     *               "user": {
     *                 "id": 20,
     *                 "nombre": "Luis",
     *                 "apellidos": "García",
     *                 "email": "luis@example.com"
     *               }
     *             },
     *             "especialista": {
     *               "id": 2,
     *               "user": {
     *                 "id": 10,
     *                 "nombre": "María",
     *                 "apellidos": "Pérez",
     *                 "email": "maria@example.com"
     *               }
     *             },
     *             "cita": {
     *               "id_cita": 33,
     *               "fecha_hora_cita": "2025-05-01 10:00:00"
     *             }
     *           }
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado como especialista",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No autorizado como especialista.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener los historiales",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al obtener los historiales.")
     *     )
     *   )
     * )
     *
     * @return JsonResponse
     */
    public function listarHistoriales(): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;

        try {
            $especialistaId = Auth::user()->especialista->id ?? null;

            if (!$especialistaId) {
                $this->registrarLog($userId, 'listar_historiales', 'historial', null);
                $respuesta = ['message' => 'No autorizado como especialista.'];
                $codigo = 403;
                return response()->json($respuesta, $codigo);
            }

            $historiales = Historial::with(['paciente.user', 'cita', 'especialista.user'])
                ->where('id_especialista', $especialistaId)
                ->orderBy('fecha', 'desc')
                ->get();

            $this->registrarLog($userId, 'listar_historiales', 'historial', null);
            $respuesta = ['message' => 'Historiales obtenidos correctamente', 'data' => $historiales];
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al obtener los historiales.'];
            $this->logError($userId, 'Error al listar historiales: ' . $e->getMessage(), $e->getTrace());
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Listar entradas del historial del paciente autenticado.
     *
     * RUTA:
     *  GET /mis-historiales
     * ROLES:
     *  paciente
     *
     * @OA\Get(
     *   path="/mis-historiales",
     *   summary="Listar historiales del paciente autenticado",
     *   description="Devuelve las entradas de historial asociadas al paciente autenticado.",
     *   tags={"Historial"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Historiales del paciente obtenidos correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Historiales del paciente obtenidos correctamente"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           example={
     *             "id": 12,
     *             "id_paciente": 5,
     *             "id_especialista": 2,
     *             "fecha": "2025-05-15",
     *             "comentarios_paciente": "Estoy siguiendo la dieta sin problemas.",
     *             "observaciones_especialista": "Peso estable.",
     *             "recomendaciones": "Añadir más actividad física.",
     *             "dieta": "Plan hipocalórico",
     *             "lista_compra": "Pescado, fruta, verdura",
     *             "especialista": {
     *               "id": 2,
     *               "user": {
     *                 "id": 10,
     *                 "nombre": "María",
     *                 "apellidos": "Pérez",
     *                 "email": "maria@example.com"
     *               }
     *             },
     *             "cita": {
     *               "id_cita": 40,
     *               "fecha_hora_cita": "2025-05-15 09:00:00"
     *             },
     *             "paciente": {
     *               "id": 5,
     *               "user": {
     *                 "id": 20,
     *                 "nombre": "Luis",
     *                 "apellidos": "García",
     *                 "email": "luis@example.com"
     *               }
     *             }
     *           }
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado como paciente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No autorizado como paciente.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener los historiales del paciente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al obtener los historiales del paciente.")
     *     )
     *   )
     * )
     *
     * @return JsonResponse
     */
    public function historialesPorPaciente(): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;

        try {
            $pacienteId = Auth::user()->paciente->id ?? null;

            if (!$pacienteId) {
                $this->registrarLog($userId, 'listar_historiales_paciente', 'historial', null);
                $respuesta = ['message' => 'No autorizado como paciente.'];
                $codigo = 403;
                return response()->json($respuesta, $codigo);
            }

            $historiales = Historial::with(['especialista.user', 'cita', 'paciente.user'])
                ->where('id_paciente', $pacienteId)
                ->orderBy('fecha', 'desc')
                ->get();

            $this->registrarLog($userId, 'listar_historiales_paciente', 'historial', null);
            $respuesta = ['message' => 'Historiales del paciente obtenidos correctamente', 'data' => $historiales];
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al obtener los historiales del paciente.'];
            $this->logError($userId, 'Error al listar historiales de paciente: ' . $e->getMessage(), $e->getTrace());
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Crear una nueva entrada en el historial médico.
     *
     * Solo especialistas pueden crear nuevas entradas.
     *
     * RUTA:
     *  POST /historial/
     *
     * BODY (JSON):
     *  {
     *    "id_paciente": 5,
     *    "fecha": "2025-05-01",
     *    "observaciones_especialista": "texto opcional",
     *    "recomendaciones": "texto opcional",
     *    "dieta": "texto opcional",
     *    "lista_compra": "texto opcional"
     *  }
     *
     * @OA\Post(
     *   path="/historial/",
     *   summary="Crear nueva entrada de historial",
     *   description="Crea una nueva entrada en el historial médico de un paciente (solo especialista).",
     *   tags={"Historial"},
     *   security={{"sanctum":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"id_paciente","fecha"},
     *       @OA\Property(property="id_paciente", type="integer", example=5),
     *       @OA\Property(property="fecha", type="string", format="date", example="2025-05-01"),
     *       @OA\Property(property="observaciones_especialista", type="string", nullable=true, example="Control de seguimiento mensual."),
     *       @OA\Property(property="recomendaciones", type="string", nullable=true, example="Reducir azúcares añadidos."),
     *       @OA\Property(property="dieta", type="string", nullable=true, example="Dieta mediterránea."),
     *       @OA\Property(property="lista_compra", type="string", nullable=true, example="Fruta, verdura, legumbres, pescado.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Historial creado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Historial creado correctamente"),
     *       @OA\Property(property="data", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado como especialista",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No autorizado como especialista")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Error de validación",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error de validación"),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al crear el historial",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al crear el historial.")
     *     )
     *   )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function nuevaEntrada(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 201;

        try {

            $especialista = Auth::user()->especialista;

            if (!$especialista) {
                return response()->json(['message' => 'No autorizado como especialista'], 403);
            }

            $validated = $request->validate([
                'id_paciente' => 'required|exists:pacientes,id',
                'fecha' => 'required|date',
                'observaciones_especialista' => 'nullable|string',
                'recomendaciones' => 'nullable|string',
                'dieta' => 'nullable|string',
                'lista_compra' => 'nullable|string',
            ]);

            $historial = Historial::create([...$validated, 'id_especialista' => $especialista->id]);

            $this->registrarLog($userId, 'crear_historial', 'historial', $historial->id);
            $respuesta = ['message' => 'Historial creado correctamente', 'data' => $historial];
        } catch (\Illuminate\Validation\ValidationException $e) {
            $codigo = 422;
            $respuesta = ['message' => 'Error de validación', 'errors' => $e->errors()];
            $this->logError($userId, 'Error de validación al crear historial', $e->errors());
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al crear el historial.'];
            $this->logError($userId, 'Error al crear historial: ' . $e->getMessage(), $e->getTrace());
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Actualizar una entrada de historial existente.
     *
     * RUTA:
     *  PUT /historial/{id}
     *
     * BODY (JSON):
     *  {
     *    "comentarios_paciente": "...",
     *    "observaciones_especialista": "...",
     *    "recomendaciones": "...",
     *    "dieta": "...",
     *    "lista_compra": "..."
     *  }
     *
     * @OA\Put(
     *   path="/historial/{id}",
     *   summary="Actualizar una entrada de historial",
     *   description="Actualiza campos de una entrada de historial (comentarios, observaciones, recomendaciones, dieta, lista de compra).",
     *   tags={"Historial"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID de la entrada de historial a actualizar",
     *     @OA\Schema(type="integer", example=10)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="comentarios_paciente", type="string", nullable=true, example="Me encuentro mejor."),
     *       @OA\Property(property="observaciones_especialista", type="string", nullable=true, example="Ha perdido 2 kg desde la última visita."),
     *       @OA\Property(property="recomendaciones", type="string", nullable=true, example="Continuar con el plan."),
     *       @OA\Property(property="dieta", type="string", nullable=true, example="Dieta hipocalórica."),
     *       @OA\Property(property="lista_compra", type="string", nullable=true, example="Verduras, frutas, arroz integral.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Historial actualizado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Historial actualizado correctamente"),
     *       @OA\Property(property="data", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Historial no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Historial no encontrado.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Error de validación",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error de validación"),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al actualizar el historial",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al actualizar el historial.")
     *     )
     *   )
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function actualizarEntrada(Request $request, int $id): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;

        try {
            $historial = Historial::find($id);

            if (!$historial) {
                $codigo = 404;
                $respuesta = ['message' => 'Historial no encontrado.'];
                $this->registrarLog($userId, 'actualizar_historial_no_encontrado', 'historial', $id);
                return response()->json($respuesta, $codigo);
            }

            $validated = $request->validate([
                'comentarios_paciente' => 'nullable|string',
                'observaciones_especialista' => 'nullable|string',
                'recomendaciones' => 'nullable|string',
                'dieta' => 'nullable|string',
                'lista_compra' => 'nullable|string',
            ]);

            $historial->update($validated);

            $this->registrarLog($userId, 'actualizar_historial', 'historial', $id);
            $respuesta = ['message' => 'Historial actualizado correctamente', 'data' => $historial];
        } catch (\Illuminate\Validation\ValidationException $e) {
            $codigo = 422;
            $respuesta = ['message' => 'Error de validación', 'errors' => $e->errors()];
            $this->logError($userId, 'Error de validación al actualizar historial', $e->errors());
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al actualizar el historial.'];
            $this->logError($userId, 'Error al actualizar historial: ' . $e->getMessage(), $e->getTrace());
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Eliminar una entrada de historial.
     *
     * RUTA:
     *  DELETE /historial/{id}
     *
     * @OA\Delete(
     *   path="/historial/{id}",
     *   summary="Eliminar una entrada de historial",
     *   description="Elimina (soft delete o hard delete según el modelo) una entrada de historial.",
     *   tags={"Historial"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID de la entrada de historial a eliminar",
     *     @OA\Schema(type="integer", example=15)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Historial eliminado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Historial eliminado correctamente")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Historial no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Historial no encontrado.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al eliminar el historial",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al eliminar el historial.")
     *     )
     *   )
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function eliminarEntrada(int $id): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;

        try {
            $historial = Historial::find($id);

            if (!$historial) {
                $codigo = 404;
                $respuesta = ['message' => 'Historial no encontrado.'];
                $this->registrarLog($userId, 'eliminar_historial_no_encontrado', 'historial', $id);
                return response()->json($respuesta, $codigo);
            }

            $historial->delete();

            $this->registrarLog($userId, 'eliminar_historial', 'historial', $id);
            $respuesta = ['message' => 'Historial eliminado correctamente'];
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al eliminar el historial.'];
            $this->logError($userId, 'Error al eliminar historial: ' . $e->getMessage(), $e->getTrace());
        }

        return response()->json($respuesta, $codigo);
    }
}