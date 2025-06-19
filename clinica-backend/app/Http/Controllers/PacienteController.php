<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;


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
     * Muestra la lista de pacientes con su nombre
     * Lista todo los pacientes registrados en la base de datos con id y su nombre y apellido.
     * @return \Illuminate\Http\JsonResponse esta función devuelve una respuesta JSON con el listado de pacientes.
     * @throws \Exception Envía un mensaje de error si no se encuentra el paciente.
     */

    public function listarPacientesPorNombre(): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;
        try{
            $user = Auth::user()->id;
            $pacientes = Paciente::all();
            $usuarios = User::all();

            if($pacientes->isempty()){
                $this->registrarLog(auth()->id(), 'listar_pacientes_por_nombre_no_encontrados',$user);
                $respuesta = ['message' => 'No hay pacientes disponibles'];
                $codigo = 404;
            }else{
                $this->registrarLog(auth()->id(), 'listar', 'listado_paciente_por_nombre', $user);
                $respuesta = [
                    'id' => $pacientes->usuario->id,
                    'nombre' => $pacientes->usuario->nombre,
                ];
            }
        }catch (\Throwable $e) {
                $this->logError(auth()->id(),'Error al obtener pacientes: ' . $e->getMessage(), $user);
                $respuesta = ['message' => 'Error al obtener los pacientes'];
                $codigo = 500;
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
        $user = Auth::user()->id;
        //Validar que el ID sea numérico
        if (!is_numeric($id)) {
            $this->registrarLog($user, 'nuevo_paciente_id_invalido', auth()->id(), null);
            return response()->json(['message' => 'ID inválido'], 400);
        }

        $usuario = User::find($id);

        if (!$usuario) {
            $this->registrarLog($user, 'usuario_para_paciente_no_encontrado', auth()->id(), $id);
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($usuario->rol !== 'usuario') {
            $this->registrarLog($user, 'usuario_no_convertible_a_paciente', auth()->id(), $id);
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

            $this->registrarLog($user, 'usuario_convertido_paciente', auth()->id(), $paciente->id);

            DB::commit();
            $respuesta = $paciente;
        } catch (\Exception $e) {
            DB::rollBack();

            //Por si el paciente nunca se crea, registrar el error
            $pacienteId = isset($paciente) ? $paciente->id : null;
            $this->registrarLog($user, 'crear_paciente_error', auth()->id(), $pacienteId);

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
                $this->registrarLog(auth()->id(), 'ver_paciente', 'user', $id);
                $respuesta = $paciente;
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
                $especialista = optional($paciente->ultimaCita->especialista);
                $usuarioEspecialista = optional($especialista->usuario);

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
                    'especialista_asociado' => $especialista ? $especialista->id : null,
                    'especialista' => $especialista ? [
                        'id_especialista' => $especialista->id,
                        'usuario' => [
                            'id_usuario' => $usuarioEspecialista->id,
                            'nombre' => $usuarioEspecialista->nombre,
                            'apellidos' => $usuarioEspecialista->apellidos,
                            'email' => $usuarioEspecialista->email,
                            'telefono' => $usuarioEspecialista->telefono,
                        ],
                    ] : null,
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

        public function pacientesConEspecialista()
    {
        // Cargar pacientes con su usuario y preparar el resultado
        $pacientes = Paciente::with('user')->get()->map(function ($paciente) {
            // Obtener la última cita con el especialista y su usuario
            $ultimaCita = $paciente->citas()
                ->with('especialista.user')
                ->orderBy('fecha_hora_cita', 'desc')
                ->first();

            // Crear un objeto de paciente con estructura anidada
            return [
                'id' => $paciente->id,
                'user_id' => $paciente->user_id,
                'numero_historial' => $paciente->numero_historial,
                'fecha_alta' => $paciente->fecha_alta,
                'fecha_baja' => $paciente->fecha_baja,
                'created_at' => $paciente->created_at,
                'updated_at' => $paciente->updated_at,
                'deleted_at' => $paciente->deleted_at,
                'ultima_cita' => $ultimaCita ? [
                    'id_cita' => $ultimaCita->id_cita,
                    'id_paciente' => $ultimaCita->id_paciente,
                    'id_especialista' => $ultimaCita->id_especialista,
                    'fecha_hora_cita' => $ultimaCita->fecha_hora_cita,
                    'tipo_cita' => $ultimaCita->tipo_cita,
                    'estado' => $ultimaCita->estado,
                    'es_primera' => $ultimaCita->es_primera,
                    'comentario' => $ultimaCita->comentario,
                    'created_at' => $ultimaCita->created_at,
                    'updated_at' => $ultimaCita->updated_at,
                    'deleted_at' => $ultimaCita->deleted_at,
                    'especialista' => $ultimaCita->especialista ? [
                        'id' => $ultimaCita->especialista->id,
                        'user_id' => $ultimaCita->especialista->user_id,
                        'especialidad' => $ultimaCita->especialista->especialidad,
                        'created_at' => $ultimaCita->especialista->created_at,
                        'updated_at' => $ultimaCita->especialista->updated_at,
                        'deleted_at' => $ultimaCita->especialista->deleted_at,
                        'usuario' => $ultimaCita->especialista->user ? [
                            'id' => $ultimaCita->especialista->user->id,
                            'nombre' => $ultimaCita->especialista->user->nombre,
                            'apellidos' => $ultimaCita->especialista->user->apellidos,
                            'dni_usuario' => $ultimaCita->especialista->user->dni_usuario,
                            'email' => $ultimaCita->especialista->user->email,
                            'email_verified_at' => $ultimaCita->especialista->user->email_verified_at,
                            'direccion' => $ultimaCita->especialista->user->direccion,
                            'fecha_nacimiento' => $ultimaCita->especialista->user->fecha_nacimiento,
                            'telefono' => $ultimaCita->especialista->user->telefono,
                            'created_at' => $ultimaCita->especialista->user->created_at,
                            'updated_at' => $ultimaCita->especialista->user->updated_at,
                            'deleted_at' => $ultimaCita->especialista->user->deleted_at,
                        ] : null,
                    ] : null,
                ] : null,
                'especialista' => $ultimaCita && $ultimaCita->especialista ? [
                    'id' => $ultimaCita->especialista->id,
                    'user_id' => $ultimaCita->especialista->user_id,
                    'especialidad' => $ultimaCita->especialista->especialidad,
                    'created_at' => $ultimaCita->especialista->created_at,
                    'updated_at' => $ultimaCita->especialista->updated_at,
                    'deleted_at' => $ultimaCita->especialista->deleted_at,
                    'usuario' => $ultimaCita->especialista->user ? [
                        'id' => $ultimaCita->especialista->user->id,
                        'nombre' => $ultimaCita->especialista->user->nombre,
                        'apellidos' => $ultimaCita->especialista->user->apellidos,
                        'dni_usuario' => $ultimaCita->especialista->user->dni_usuario,
                        'email' => $ultimaCita->especialista->user->email,
                        'email_verified_at' => $ultimaCita->especialista->user->email_verified_at,
                        'direccion' => $ultimaCita->especialista->user->direccion,
                        'fecha_nacimiento' => $ultimaCita->especialista->user->fecha_nacimiento,
                        'telefono' => $ultimaCita->especialista->user->telefono,
                        'created_at' => $ultimaCita->especialista->user->created_at,
                        'updated_at' => $ultimaCita->especialista->user->updated_at,
                        'deleted_at' => $ultimaCita->especialista->user->deleted_at,
                    ] : null,
                ] : null,
                'usuario' => $paciente->user ? [
                    'id' => $paciente->user->id,
                    'nombre' => $paciente->user->nombre,
                    'apellidos' => $paciente->user->apellidos,
                    'dni_usuario' => $paciente->user->dni_usuario,
                    'email' => $paciente->user->email,
                    'email_verified_at' => $paciente->user->email_verified_at,
                    'direccion' => $paciente->user->direccion,
                    'fecha_nacimiento' => $paciente->user->fecha_nacimiento,
                    'telefono' => $paciente->user->telefono,
                    'created_at' => $paciente->user->created_at,
                    'updated_at' => $paciente->user->updated_at,
                    'deleted_at' => $paciente->user->deleted_at,
                ] : null,
            ];
        });

        return response()->json($pacientes);
    }


    /**
     * Actualiza datos del paciente, requiere confirmar password.
     *
     * @param Request $request
     * @param int $idPaciente
     * @return \Illuminate\Http\JsonResponse
     */
    public function actualizarPaciente(Request $request, int $idPaciente)
    {
        $respuesta = null;

        try {
            $paciente = Paciente::with('user')->findOrFail($idPaciente);

            if (auth()->user()->id !== $paciente->user_id) {
                $respuesta = response()->json(['error' => 'No autorizado'], 403);
            } else {
                $validator = Validator::make($request->all(), [
                    'nombre' => 'required|string|max:255',
                    'apellidos' => 'required|string|max:255',
                    'dni_usuario' => 'required|string|max:20|unique:users,dni_usuario,' . $paciente->user_id,
                    'email' => 'required|email|max:255|unique:users,email,' . $paciente->user_id,
                    'direccion' => 'nullable|string|max:255',
                    'fecha_nacimiento' => 'nullable|date',
                    'telefono' => 'nullable|string|max:20',
                    'password_actual' => 'required|string',
                ]);

                if ($validator->fails()) {
                    $respuesta = response()->json(['errors' => $validator->errors()], 422);
                } elseif (!\Hash::check($request->password_actual, $paciente->user->password)) {
                    $respuesta = response()->json(['error' => 'Contraseña actual incorrecta'], 422);
                } else {
                    $user = $paciente->user;
                    $user->nombre = $request->nombre;
                    $user->apellidos = $request->apellidos;
                    $user->dni_usuario = $request->dni_usuario;
                    $user->email = $request->email;
                    $user->direccion = $request->direccion;
                    $user->fecha_nacimiento = $request->fecha_nacimiento;
                    $user->telefono = $request->telefono;
                    $user->save();

                    $respuesta = response()->json(['mensaje' => 'Datos actualizados correctamente', 'paciente' => $paciente->load('user')]);
                }
            }
        } catch (\Exception $e) {
            $respuesta = response()->json(['error' => 'Paciente no encontrado'], 404);
        }

        return $respuesta;
    }

    /**
     * Cambia la contraseña del paciente.
     *
     * @param Request $request
     * @param int $idPaciente
     * @return \Illuminate\Http\JsonResponse
     */
    public function cambiarPassword(Request $request, int $idPaciente)
    {
        $respuesta = null;

        try {
            $paciente = Paciente::with('user')->findOrFail($idPaciente);

            if (auth()->user()->id !== $paciente->user_id) {
                $respuesta = response()->json(['error' => 'No autorizado'], 403);
            } else {
                $validator = Validator::make($request->all(), [
                    'password_actual' => 'required|string',
                    'password_nuevo' => 'required|string|min:8|confirmed',
                ]);

                if ($validator->fails()) {
                    $respuesta = response()->json(['errors' => $validator->errors()], 422);
                } elseif (!\Hash::check($request->password_actual, $paciente->user->password)) {
                    $respuesta = response()->json(['error' => 'Contraseña actual incorrecta'], 422);
                } else {
                    $user = $paciente->user;
                    $user->password = Hash::make($request->password_nuevo);
                    $user->save();

                    $respuesta = response()->json(['mensaje' => 'Contraseña actualizada correctamente']);
                }
            }
        } catch (\Exception $e) {
            $respuesta = response()->json(['error' => 'Paciente no encontrado'], 404);
        }

        return $respuesta;
    }

    /**
     * obtener datos de paciente a partir del id de usuario.
     */

    public function obtenerPacientePorUsuario($userId)
    {
        $paciente = Paciente::where('user_id', $userId)->first();

        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }
        return response()->json($paciente);
    }



}
