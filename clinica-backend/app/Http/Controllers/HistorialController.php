<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;

class HistorialController extends Controller
{
    use Loggable;

    /**
     * Constructor con middleware de autenticación y control de roles.
     */
/*     public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    } */

    /**
     * Listar entradas del historial de un especialista logueado.
     * Devuelve todas las entradas de historiales de los pacientes del especialista.
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
     * Listar entradas del historial de un paciente logueado.
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

            $historial = Historial::create([ ...$validated, 'id_especialista' => $especialista->id ]);
            
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
