<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Log;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;


class HistorialController extends Controller
{
    use Loggable;

    /**
     * Listar todos los historiales médicos.
     * Este método obtiene todos los historiales médicos de la base de datos.
     * @throws \Throwable la excepción se lanza si hay un error al listar los historiales.
     * @return JsonResponse devolverá una respuesta JSON con el estado de la operación.
     */
    public function listarHistoriales(): JsonResponse
    {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();
        $respuesta = [];
        $codigo = 200;

        try {
            $historiales = Historial::all();

            if ($historiales->isEmpty()) {
                $this->registrarLog($userId, 'No hay historiales disponibles', null, $historiales->id);
                $codigo = 404;
                $respuesta = ['message' => 'No hay historiales disponibles'];
            } else {
                $this->registrarLog($userId, 'Listado de historiales', null, $historiales->id);
                $respuesta = $historiales;
            }
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno del servidor'];
            $this->registrarLog($userId, 'index', null, 'Error al listar historiales: ' . $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Ver un historial médico por ID.
     * Este método busca un historial médico por su ID y devuelve los detalles.
     * @throws \Throwable la excepción se lanza si hay un error al consultar el historial.
     * @param int $id el ID del historial médico a consultar.
     * @return JsonResponse devolverá una respuesta JSON con el estado de la operación.
     */
    public function verHistorial(int $id): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;
        $respuesta = [];

        try {
            $historial = Historial::find($id);

            if (!$historial) {
                $this->registrarLog($userId, 'show -> Historial no encontrado', $id, $historial->id);
                $codigo = 404;
                $respuesta = ['message' => 'Historial no encontrado'];
            } else {
                $this->registrarLog($userId, 'show -> Historial consultado', $id, $historial->id);
                $respuesta = $historial;
            }
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno del servidor'];
            $this->registrarLog($userId, 'show', $id, 'Error al consultar historial: ' . $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Crear un nuevo historial médico.
     * Este método recibe los datos del historial a crear y los valida.
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable la excepción se lanza si hay un error al crear el historial.
     * @param Request $solicitud esta solicitud contiene los datos del historial médico.
     * @return JsonResponse devolverá una respuesta JSON con el estado de la operación.
     */
    public function nuevoHistorial(Request $solicitud): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;
        $respuesta = [];

        try {
            $solicitud->validate([
                'paciente_id' => 'required|exists:pacientes,id',
                'especialista_id' => 'required|exists:especialistas,id',
                'comentarios_paciente' => 'nullable|string',
                'observaciones_especialista' => 'nullable|string',
                'recomendaciones' => 'nullable|string',
                'dieta' => 'nullable|string',
                'lista_compra' => 'nullable|string',
            ]);

            $historial = Historial::create($solicitud->all());

            $this->registrarLog($userId, 'store->Historial creado', 'Historial', $historial->id);

            $respuesta = $historial;

        } catch (\Illuminate\Validation\ValidationException $e) {
            $codigo = 422;
            $respuesta = ['message' => 'Error de validación', 'errors' => $e->errors()];
            $this->registrarLog($userId, 'store', null, 'Error de validación: ' . json_encode($e->errors()));

        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno del servidor'];
            $this->registrarLog($userId, 'store->Error al crear historial: ' . $e->getMessage(), 'Historial', );
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * 
     * Actualizar un historial médico.
     * Este método recibe los datos del historial a actualizar y los valida.
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable la excepción se lanza si hay un error al actualizar el historial.
     * @param Request $solicitud esta solicitud contiene los datos del historial médico a actualizar.
     * @param int $id el id del historial a actualizar.
     * @return JsonResponse devolverá una respuesta JSON con el estado de la operación.
     */
    public function actualizarHistorial(Request $solicitud, int $id): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;
        $respuesta = [];

        try {
            $historial = Historial::find($id);

            if (!$historial) {
                $codigo = 404;
                $respuesta = ['message' => 'Historial no encontrado'];
                $this->registrarLog($userId, 'update->Historial no encontrado', 'Historial', $historial->id);
            } else {
                $solicitud->validate([
                    'comentarios_paciente' => 'nullable|string',
                    'observaciones_especialista' => 'nullable|string',
                    'recomendaciones' => 'nullable|string',
                    'dieta' => 'nullable|string',
                    'lista_compra' => 'nullable|string',
                ]);

                $historial->update($solicitud->all());

                $this->registrarLog($userId, 'update->Historial actualizado', 'Historial', $historial->id);

                $respuesta = [
                    'message' => 'Historial actualizado correctamente',
                    'historial' => $historial,
                ];
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $codigo = 422;
            $respuesta = ['message' => 'Error de validación', 'errors' => $e->errors()];
            $this->registrarLog($userId, 'update -> Error de validación: ' . json_encode($e->errors()), 'Historial', $historial->id);
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno del servidor'];
            $this->registrarLog($userId, 'update->Error al actualizar historial '. $e->getMessage(), 'Historial', $historial->id );
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Eliminar un historial médico.
     * Este método elimina un historial médico por su ID.
     * @throws \Throwable la excepción se lanza si hay un error al eliminar el historial.
     * @param int $id el ID del historial médico a eliminar.
     * @return JsonResponse devolverá una respuesta JSON con el estado de la operación.
     */
    public function borrarHistorial(int $id): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;
        $respuesta = [];

        try {
            $historial = Historial::find($id);

            if (!$historial) {
                $codigo = 404;
                $respuesta = ['message' => 'Historial no encontrado'];
                $this->registrarLog($userId, 'destroy -> Historial no encontrado', $id, $historial->id);
            } else {
                $historial->delete();
                $this->registrarLog($userId, 'destroy -> Historial eliminado', $id, $historial->id);

                $respuesta = ['message' => 'Historial eliminado correctamente'];
            }
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al eliminar el historial'];
            $this->registrarLog($userId, 'destroy -> Error al eliminar historial: ' . $e->getMessage(), 'Historial', $historial->id);
        }

        return response()->json($respuesta, $codigo);
    }

}
