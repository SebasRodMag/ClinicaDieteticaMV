<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cita;
use App\Models\Log;
use App\Traits\Loggable;
    
use Illuminate\Http\JsonResponse;

class CitaController extends Controller
{
    use Loggable;

    /**
     * Función para listar todas las citas.
     * Esta función obtiene todas las citas de la base de datos, incluyendo la información del paciente y del especialista.
     * Registra un log de la acción realizada.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el listado de citas.
     */
    public function listarCitas(): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;

        $citas = Cita::with(['paciente', 'especialista'])->get();

        if ($citas->isEmpty()) {
            $this->registrarLog(auth()->id(), 'listar_citas', 'No hay citas registradas');
            $respuesta = ['message' => 'No hay citas registradas'];
        } else {
            $this->registrarLog(auth()->id(), 'listar_citas', 'Listado de citas consultado');
            $respuesta = ['citas' => $citas];
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Función para ver una cita específica.
     * Esta función busca una cita por su ID y devuelve sus detalles, incluyendo el paciente y el especialista.
     * Registra un log de la acción realizada.
     * @param int $id ID de la cita a consultar.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con los detalles de la cita o un mensaje de error si no se encuentra.
     */
    public function verCita(int $id): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;

        $cita = Cita::with(['paciente', 'especialista'])->find($id);

        if (!$cita) {
            $this->registrarLog(auth()->id(), 'mostrar_citas_fallido', 'citas',$id );
            $respuesta = ['message' => 'Cita no encontrada'];
            $codigo = 404;
        } else {
            $this->registrarLog(auth()->id(), 'mostrar_citas', 'citas',$id );
            $respuesta = ['cita' => $cita];
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Función para crear una nueva cita.
     * Esta función recibe los datos de la cita a través de una solicitud y crea una nueva entrada en la base de datos.
     * Registra un log de la acción realizada.
     * Valida los datos de entrada y maneja posibles errores durante la creación.
     * 
     * @param \Illuminate\Http\Request $solicitud contiene los datos de la cita a crear.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el estado de la operación y los detalles de la cita creada.
     * @throws \Illuminate\Validation\ValidationException lanza una excepción si los datos no cumplen con las reglas de validación.
     * @throws \Exception lanza una excepción si ocurre un error al crear la cita.
     */

    public function nuevaCita(Request $solicitud): JsonResponse
    {
        $respuesta = [];
        $codigo = 201;

        $validar = $solicitud->validate([
            'paciente_id'     => 'required|exists:pacientes,id',
            'especialista_id' => 'required|exists:especialistas,id',
            'fecha_hora'      => 'required|date_format:Y-m-d H:i:s|after:now',
            'tipo'            => 'required|string|max:50',
            'comentarios'     => 'nullable|string',
        ]);

        try {
            //Las citas tienen el estado 'pendiente' por defecto
            $datos = array_merge($validar, ['estado' => 'pendiente']);
            $cita = Cita::create($datos);

            $this->registrarLog(auth()->id(), 'crear_cita', "Cita creada ID {$cita->id}");

            $respuesta = [
                'message' => 'Cita creada correctamente',
                'cita'    => $cita,
            ];
            $codigo = 201;

        } catch (\Exception $e) {
            $this->registrarLog(auth()->id(), 'crear_cita_error', "Error al crear cita: " . $e->getMessage());
            $respuesta = ['message' => 'Error interno al crear la cita'];
            $codigo = 500;
        }

        return response()->json($respuesta, $codigo);
    }



    /**
     * Función para actualizar una cita existente.
     * Esta función recibe los datos actualizados de la cita y los aplica a la base de datos.
     * Registra un log de la acción realizada.
     * Valida los datos de entrada y maneja posibles errores durante la actualización.
     * 
     * @param \Illuminate\Http\Request $solicitud contiene los datos actualizados de la cita.
     * @param int $id ID de la cita a actualizar.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el estado de la operación y los detalles de la cita actualizada o un mensaje de error si no se encuentra.
     */
    public function actualizarCita(Request $solicitud, int $id): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;

        $cita = Cita::find($id);

        if (!$cita) {
            $this->registrarLog(auth()->id(), 'actualizar_cita_fallido', "Cita no encontrada", $id);
            $respuesta = ['message' => 'Cita no encontrada'];
            $codigo = 404;
        } else {
            $validar = $solicitud->validate([
                'fecha_hora'  => 'nullable|date_format:Y-m-d H:i:s|after:now',
                'tipo'        => 'nullable|string|max:50',
                'estado'      => 'nullable|string|in:pendiente,confirmada,cancelada,finalizada',
                'comentarios' => 'nullable|string',
            ]);

            $cita->update($validar);

            $this->registrarLog(auth()->id(), 'actualizar_cita', "Cita ID $id actualizada");

            $respuesta = [
                'message' => 'Cita actualizada correctamente',
                'cita' => $cita,
            ];
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Función para borrar una cita. Exclusiva para administradores.
     * Esta función elimina una cita de la base de datos por su ID.
     * Registra un log de la acción realizada.
     * Maneja posibles errores durante la eliminación.
     * Se valida que el ID sea numérico y positivo, y que el usuario tenga el rol de administrador.
     * @param int $id ID de la cita a eliminar.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el estado de la operación y un mensaje de confirmación o error.
     */

    public function borrarCita($id): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;
        $userId = auth()->id();

        // Validar que el id, al menos, sea un número positivo
        if (!is_numeric($id) || intval($id) <= 0) {
            $this->registrarLog($userId, 'borrar_cita_error', "ID de cita inválido: $id");
            $respuesta = ['message' => 'ID de cita inválido'];
            $codigo = 400;
            return response()->json($respuesta, $codigo);
        }

        //Comprobar que el usuario es administrador
        if (!auth()->user()->hasRole('administrador')) {
            $this->registrarLog($userId, 'borrar_cita_no_autorizado', "Usuario no autorizado para borrar cita ID $id");
            $respuesta = ['message' => 'No autorizado para eliminar citas'];
            $codigo = 403;
            return response()->json($respuesta, $codigo);
        }

        $cita = Cita::find($id);

        if (!$cita) {
            $this->registrarLog($userId, 'borrar_cita_fallido', "Cita ID $id no encontrada");
            $respuesta = ['message' => 'Cita no encontrada'];
            $codigo = 404;
            return response()->json($respuesta, $codigo);
        }

        if ($cita->delete()) {
            $this->registrarLog($userId, 'borrar_cita_exito', "Cita ID $id eliminada");
            $respuesta = ['message' => 'Cita eliminada correctamente'];
        } else {
            $this->registrarLog($userId, 'borrar_cita_error', "Error al eliminar cita ID $id");
            $respuesta = ['message' => 'No se pudo eliminar la cita'];
            $codigo = 500;
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Función para cancelar una cita.
     * Esta función permite a un paciente o especialista cancelar una cita existente a la que estén asociados
     * y actualizar su estado a 'cancelada'.
     * Se valida que el usuario que intenta cancelar la cita sea el paciente o el especialista relacionado con la cita.
     * Registra un log de la acción realizada.
     * 
     * @param int $usuarioId ID del usuario que realiza la acción.
     * @param string $accion Acción realizada.
     * @param string $descripcion Descripción de la acción.
     * @param string $tabla Tabla afectada (opcional).
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el estado de la operación y un mensaje de confirmación o error.
     * @throws \Exception lanza una excepción si ocurre un error al cancelar la cita.
     */
    public function cancelarCita(int $id): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;

        $cita = Cita::with(['paciente', 'especialista'])->find($id);

        if (!$cita) {
            $this->registrarLog(auth()->id(), 'cancelar_cita_fallido', "Cita ID $id no encontrada");
            $respuesta = ['message' => 'Cita no encontrada'];
            $codigo = 404;
        } else {
            $userId = auth()->id();

            //Se comprueba que el usuario es paciente o especialista relacionado con la cita
            if ($cita->paciente->user_id!== $userId && $cita->especialista->user_id !== $userId) {
                $this->registrarLog($userId, 'cancelar_cita_no_autorizado', "Intento no autorizado de cancelar cita ID $id");
                $respuesta = ['message' => 'No autorizado para cancelar esta cita'];
                $codigo = 403;
            } else {
                if ($cita->estado === 'cancelada') {
                    $respuesta = ['message' => 'La cita ya está cancelada'];
                    $codigo = 400;
                } else {
                    try {
                        $cita->estado = 'cancelada';
                        $cita->save();

                        $this->registrarLog($userId, 'cancelar_cita', "Cita ID $id cancelada por usuario $userId");
                        $respuesta = ['message' => 'Cita cancelada correctamente', 'cita' => $cita];
                    } catch (\Exception $e) {
                        $this->registrarLog($userId, 'cancelar_cita_error', "Error al cancelar cita ID $id: ".$e->getMessage());
                        $respuesta = ['message' => 'Error interno al cancelar la cita'];
                        $codigo = 500;
                    }
                }
            }
        }

        return response()->json($respuesta, $codigo);
    }


}
