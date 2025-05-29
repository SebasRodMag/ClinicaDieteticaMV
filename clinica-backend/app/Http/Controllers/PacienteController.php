<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Log;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;


class PacienteController extends Controller
{
    use Loggable;

    /**
     * Muestra una lista de pacientes.
     * Muestra todos los pacientes registrados en la base de datos.
     * @return \Illuminate\Http\JsonResponse esta función devuelve una respuesta JSON con el listado de pacientes.
     * 
     */
    public function listarPacientes(): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;

        $pacientes = Paciente::all();

        if ($pacientes->isEmpty()) {
            $this->registrarLog(auth()->id(), 'listar', 'pacientes', null);
            $respuesta = ['message' => 'No hay pacientes disponibles'];
            $codigo = 404;
        } else {
            $this->registrarLog(auth()->id(), 'listar', 'pacientes', null);
            $respuesta = $pacientes;
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Crea un nuevo paciente.
     * Registra un nuevo paciente en la base de datos.
     * Se valida que el usuario asociado exista y no esté ya registrado como paciente.
     * 
     * @param \Illuminate\Http\Request $solicitud recibe los datos del paciente
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el paciente creado o un mensaje de error.
     * @throws \Illuminate\Validation\ValidationException si los datos no cumplen con las reglas de validación.
     * @throws \Exception lanza excepción si ocurre un error al crear el paciente.
     * 
     */
    public function viejoPaciente(Request $solicitud, $id): JsonResponse
    {
        $respuesta = [];
        $codigo = 201;

        //Validar que el ID sea numérico
        if (!is_numeric($id)) {
            $this->registrarLog('user', 'nuevo_paciente_id_invalido', auth()->id(), null);
            return response()->json(['message' => 'ID inválido'], 400);
        }

        $usuario = User::find($id);

        if (!$usuario) {
            $this->registrarLog('user', 'usuario_para_paciente_no_encontrado', auth()->id(), $id);
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($usuario->rol !== 'usuario') {
            $this->registrarLog('user', 'usuario_no_convertible_a_paciente', auth()->id(), $id);
            return response()->json(['message' => 'Este usuario no puede ser convertido a paciente'], 403);
        }

        try {
            DB::beginTransaction();

            //Actualizar rol
            $usuario->rol = 'paciente';
            $usuario->save();

            $paciente = Paciente::create([
                'user_id' => $usuario->id,
            ]);

            $this->registrarLog('paciente', 'usuario_convertido_paciente', auth()->id(), $paciente->id);

            DB::commit();
            $respuesta = $paciente;
        } catch (\Exception $e) {
            DB::rollBack();

            //Por si el paciente nunca se crea, registrar el error
            $pacienteId = isset($paciente) ? $paciente->id : null;
            $this->registrarLog('paciente', 'crear_paciente_error', auth()->id(), $pacienteId);

            $respuesta = ['message' => 'Error interno al crear el paciente'];
            $codigo = 500;
        }

    return response()->json($respuesta, $codigo);
}


    /**
     * Muestra un paciente específico.
     * Busca un paciente por su ID y devuelve sus datos.
     * Se valida que el ID sea numérico y que el paciente exista.
     * @param int $id ID del paciente a buscar
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con los datos del paciente o un mensaje de error.
     * 
     */
    public function verPaciente($id)
    {
        $respuesta = [];
        $codigo = 200;

        if (!is_numeric($id)) {
            $this->registrarLog(auth()->id(), 'mostrar_usuario', 'user', $id);
            $respuesta = ['message' => 'ID inválido'];
            $codigo = 400;
        } else {
            $paciente = Paciente::find($id);

            if (!$paciente) {
                $this->registrarLog(auth()->id(), 'usuario_no_encontrado', 'user', $id);
                $respuesta = ['message' => 'Paciente no encontrado'];
                $codigo = 404;
            } else {
                $this->registrarLog(auth()->id(), 'ver_usuario_error', 'user', $id);
                $respuesta = $paciente;
            }
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Actualiza los datos de un paciente.
     * Actualiza la información de un paciente existente en la base de datos.
     * Se valida que el ID sea numérico y que el paciente exista.
     * @param \Illuminate\Http\Request $solicitud lleva los datos del paciente a actualizar
     * @param int $id ID del paciente a actualizar
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con los datos del paciente actualizado o un mensaje de error.
     * @throws \Illuminate\Validation\ValidationException lanza excepción si los datos no cumplen con las reglas de validación.
     * 
     */
    public function actualizarPaciente(Request $solicitud, $id)
    {
        $respuesta = [];
        $codigo = 200;

        if (!is_numeric($id)) {
            $this->registrarLog(auth()->id(), 'actualizar_paciente_id_invalido', 'paciente', $id);
            $respuesta = ['message' => 'ID inválido'];
            $codigo = 400;
        } else {
            $paciente = Paciente::find($id);

            if (!$paciente) {
                $this->registrarLog(auth()->id(), 'actualizar_paciente_invalido', 'paciente', $id);
                $respuesta = ['message' => 'Paciente no encontrado'];
                $codigo = 404;
            } else {
                $solicitud->validate([
                    'nss' => 'nullable|string|max:20',
                    'fecha_nacimiento' => 'nullable|date',
                ]);

                $paciente->update($solicitud->only(['nss', 'fecha_nacimiento']));

                $this->registrarLog(auth()->id(), 'actualizar_paciente', 'paciente', $id);

                $respuesta = [
                    'message' => 'Paciente actualizado correctamente',
                    'paciente' => $paciente,
                ];
            }
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Borrar un paciente (softDelete).
     * Este método elimina un paciente por su ID.
     * Se valida que el ID sea numérico y que el paciente exista.
     * @param int $id ID del paciente a eliminar
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con un mensaje de éxito o un mensaje de error.
     * 
     */
    public function borrarPaciente($id)
    {
        $respuesta = [];
        $codigo = 200;

        if (!is_numeric($id)) {
            $this->registrarLog(auth()->id(), 'borrar_paciente_id_invalido', 'paciente', null);
            $respuesta = ['message' => 'ID inválido'];
            $codigo = 400;
        } else {
            $paciente = Paciente::find($id);

            if (!$paciente) {
                $this->registrarLog(auth()->id(), 'borrar_paciente_id_no_encontrado', 'paciente', $id);
                $respuesta = ['message' => 'Paciente no encontrado'];
                $codigo = 404;
            } else {
                if ($paciente->delete()) {
                    $this->registrarLog(auth()->id(), 'borrar_paciente', 'paciente', $id);
                    $respuesta = ['message' => 'Paciente eliminado correctamente'];
                } else {
                    $this->registrarLog(auth()->id(), 'borrar_paciente_error', 'paciente', $id);
                    $respuesta = ['message' => 'No se pudo eliminar el paciente'];
                    $codigo = 500;
                }
            }
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Devuelve información completa de todos los pacientes,
     * incluyendo la última cita (estado y especialista asociado).
     * Esta función obtiene todos los pacientes y sus datos asociados, incluyendo la última cita.
     * Se utiliza la relación definida en el modelo Paciente para obtener la última cita.
     * @throws \Throwable si ocurre un error al obtener los pacientes.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con la información de los pacientes.
     */
        //Para devolver una lista completa de pacientes con sus datos asociados, incluyendo la última cita.
        // originalmente se empleaba una consulta compleja, por medio de un left join, pero aquí se simplifica utilizando Eloquent,
        // utilizando la relación definida en el modelo Paciente para obtener la última cita.
        public function getFullPacientes(): JsonResponse
        {
            $respuesta = [];
            $codigo = 200;

            try {
                $pacientes = Paciente::with(['usuario', 'ultimaCita'])->get();

                $resultado = $pacientes->map(function ($paciente) {
                    return [
                        'id' => $paciente->usuario->id,
                        'nombre' => $paciente->usuario->nombre,
                        'apellidos' => $paciente->usuario->apellidos,
                        'telefono' => $paciente->usuario->telefono,
                        'email' => $paciente->usuario->email,
                        'numero_historial' => $paciente->numero_historial,
                        'fecha_alta' => $paciente->fecha_alta,
                        'fecha_baja' => $paciente->fecha_baja,
                        'estado' => optional($paciente->ultimaCita)->estado,
                        'especialista_asociado' => optional($paciente->ultimaCita)->id_especialista,
                    ];
                });

                if ($resultado->isEmpty()) {
                    $this->registrarLog(auth()->id(), 'Pacientes_no_encontrados', 'paciente', null);
                    $respuesta = ['message' => 'No se encontraron pacientes'];
                    $codigo = 404;
                } else {
                    $this->registrarLog(auth()->id(), 'Pacientes_no_encontrados', 'paciente', null);
                    $respuesta = $resultado;
                }

            } catch (\Throwable $e) {
                $this->logError(auth()->id(),'Error al obtener pacientes: ' . $e->getMessage(), null);
                $respuesta = ['message' => 'Error al obtener los pacientes'];
                $codigo = 500;
            }

            return response()->json($respuesta, $codigo);
        }


}
