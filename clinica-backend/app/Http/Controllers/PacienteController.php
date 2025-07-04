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

        try {
            $pacientes = Paciente::with('user')->get();

            if ($pacientes->isEmpty()) {
                $respuesta = ['message' => 'No hay pacientes disponibles'];
                $codigo = 404;
            } else {
                $respuesta = [
                    'pacientes' => $pacientes->map(function ($paciente) {
                        return [
                            'id' => $paciente->id,
                            'user_id' => $paciente->user_id,
                            'numero_historial' => $paciente->numero_historial,
                            'fecha_alta' => $paciente->fecha_alta,
                            'fecha_baja' => $paciente->fecha_baja,
                            'created_at' => $paciente->created_at,
                            'updated_at' => $paciente->updated_at,
                            'deleted_at' => $paciente->deleted_at,
                            'user' => $paciente->user ? [
                                'id' => $paciente->user->id,
                                'nombre' => $paciente->user->nombre,
                                'apellidos' => $paciente->user->apellidos,
                                'email' => $paciente->user->email,
                            ] : null,
                        ];
                    }),
                ];
            }

            $this->registrarLog(auth()->id(), 'listar', 'pacientes');
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al obtener pacientes'];
            $this->logError(auth()->id(), 'Error al listar pacientes', $e->getMessage());
            $this->registrarLog(auth()->id(), 'error_listar', 'pacientes');
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
        $userId = Auth::id();

        try {
            $pacientes = Paciente::with('user')->get();

            if ($pacientes->isEmpty()) {
                $this->registrarLog($userId, 'listar_pacientes_por_nombre_no_encontrados', $userId);
                $respuesta = ['message' => 'No hay pacientes disponibles'];
                $codigo = 404;
            } else {
                $listado = $pacientes->map(function ($paciente) {
                    return [
                        'id' => $paciente->user->id,
                        'nombre' => $paciente->user->nombre . ' ' . $paciente->user->apellidos,
                    ];
                });

                $this->registrarLog($userId, 'listar', 'listado_pacientes_por_nombre', $userId);
                $respuesta = ['pacientes' => $listado];
            }
        } catch (\Throwable $e) {
            $this->logError($userId, 'Error al obtener pacientes: ' . $e->getMessage(), $userId);
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
        $userId = Auth::id();
        $respuesta = [];
        $codigo = 201;

        // Validar que el ID sea numérico
        $validar = Validator::make(['id' => $id], ['id' => 'required|integer']);

        if ($validar->fails()) {
            $this->registrarLog($userId, 'nuevo_paciente_id_invalido', $userId, null);
            $respuesta = ['message' => 'ID inválido'];
            $codigo = 400;
            return response()->json($respuesta, $codigo);
        }

        try {
            $usuario = User::find($id);

            if (!$usuario) {
                $this->registrarLog($userId, 'usuario_para_paciente_no_encontrado', $userId, $id);
                $respuesta = ['message' => 'Usuario no encontrado'];
                $codigo = 404;
            } elseif ($usuario->rol !== 'usuario') {
                $this->registrarLog($userId, 'usuario_no_convertible_a_paciente', $userId, $id);
                $respuesta = ['message' => 'Este usuario no puede ser convertido a paciente'];
                $codigo = 403;
            } else {
                DB::beginTransaction();

                // Actualizar rol
                $usuario->rol = 'paciente';
                $usuario->save();

                $paciente = Paciente::create(['user_id' => $usuario->id]);

                $this->registrarLog($userId, 'usuario_convertido_paciente', $userId, $paciente->id);

                DB::commit();

                $respuesta = $paciente;
                $codigo = 201;
            }
        } catch (\Exception $e) {
            DB::rollBack();

            $pacienteId = isset($paciente) ? $paciente->id : null;
            $this->registrarLog($userId, 'crear_paciente_error', $userId, $pacienteId);

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
    public function verPaciente($id): JsonResponse
    {
        $userId = auth()->id();
        $respuesta = [];
        $codigo = 200;

        if (!is_numeric($id)) {
            $this->registrarLog($userId, 'mostrar_usuario_id_invalido', 'user', $id);
            $respuesta = ['message' => 'ID inválido'];
            $codigo = 400;
        } else {
            $paciente = Paciente::find($id);

            if (!$paciente) {
                $this->registrarLog($userId, 'paciente_no_encontrado', 'user', $id);
                $respuesta = ['message' => 'Paciente no encontrado'];
                $codigo = 404;
            } else {
                $this->registrarLog($userId, 'ver_paciente', 'user', $id);
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
    public function borrarPaciente($id): JsonResponse
    {
        $userId = auth()->id();
        $respuesta = [];
        $codigo = 200;

        if (!is_numeric($id)) {
            $this->registrarLog($userId, 'borrar_paciente_id_invalido', 'paciente', null);
            $respuesta = ['message' => 'ID inválido'];
            $codigo = 400;
        } else {
            $paciente = Paciente::find($id);

            if (!$paciente) {
                $this->registrarLog($userId, 'borrar_paciente_id_no_encontrado', 'paciente', $id);
                $respuesta = ['message' => 'Paciente no encontrado'];
                $codigo = 404;
            } else {
                try {
                    $paciente->delete();
                    $this->registrarLog($userId, 'borrar_paciente', 'paciente', $id);
                    $respuesta = ['message' => 'Paciente eliminado correctamente'];
                } catch (\Exception $e) {
                    $this->registrarLog($userId, 'borrar_paciente_error', 'paciente', $id);
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
        $userId = auth()->id();
        $respuesta = [];
        $codigo = 200;

        try {
            $pacientes = Paciente::with(['usuario', 'ultimaCita', 'ultimaCita.especialista', 'ultimaCita.especialista.usuario'])->get();

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
                    'fecha_alta' => optional($paciente->fecha_alta)?->format('Y-m-d'),
                    'fecha_baja' => optional($paciente->fecha_baja)?->format('Y-m-d'),
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
                $this->registrarLog($userId, 'pacientes_no_encontrados', 'paciente', null);
                $respuesta = ['message' => 'No se encontraron pacientes'];
                $codigo = 404;
            } else {
                $this->registrarLog($userId, 'listar_pacientes_completo', 'paciente', null);
                $respuesta = $resultado;
            }
        } catch (\Throwable $e) {
            $this->logError($userId, 'Error al obtener pacientes: ' . $e->getMessage(), null);
            $respuesta = ['message' => 'Error al obtener los pacientes'];
            $codigo = 500;
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Función para listar la relación de citas de un paciente y los epecialistas adjuntando la información necesaria
     * @return JsonResponse|mixed
     */
    public function pacientesConEspecialista(): JsonResponse
    {
        $userId = auth()->id();
        $respuesta = [];
        $codigo = 200;

        try {
            $pacientes = Paciente::with('user')->get();

            $resultado = $pacientes->map(function ($paciente) {
                $ultimaCita = $paciente->citas()
                    ->with('especialista.user')
                    ->orderBy('fecha_hora_cita', 'desc')
                    ->first();

                $especialista = $ultimaCita ? $ultimaCita->especialista : null;
                $usuarioEspecialista = $especialista ? $especialista->user : null;

                return [
                    'id' => $paciente->id,
                    'user_id' => $paciente->user_id,
                    'numero_historial' => $paciente->numero_historial,
                    'fecha_alta' => optional($paciente->fecha_alta)?->format('Y-m-d'),
                    'fecha_baja' => optional($paciente->fecha_baja)?->format('Y-m-d'),
                    'created_at' => optional($paciente->created_at)?->format('Y-m-d H:i:s'),
                    'updated_at' => optional($paciente->updated_at)?->format('Y-m-d H:i:s'),
                    'deleted_at' => optional($paciente->deleted_at)?->format('Y-m-d H:i:s'),

                    'ultima_cita' => $ultimaCita ? [
                        'id_cita' => $ultimaCita->id_cita,
                        'id_paciente' => $ultimaCita->id_paciente,
                        'id_especialista' => $ultimaCita->id_especialista,
                        'fecha_hora_cita' => optional($ultimaCita->fecha_hora_cita)?->format('Y-m-d H:i:s'),
                        'tipo_cita' => $ultimaCita->tipo_cita,
                        'estado' => $ultimaCita->estado,
                        'es_primera' => $ultimaCita->es_primera,
                        'comentario' => $ultimaCita->comentario,
                        'created_at' => optional($ultimaCita->created_at)?->format('Y-m-d H:i:s'),
                        'updated_at' => optional($ultimaCita->updated_at)?->format('Y-m-d H:i:s'),
                        'deleted_at' => optional($ultimaCita->deleted_at)?->format('Y-m-d H:i:s'),

                        'especialista' => $especialista ? [
                            'id' => $especialista->id,
                            'user_id' => $especialista->user_id,
                            'especialidad' => $especialista->especialidad,
                            'created_at' => optional($especialista->created_at)?->format('Y-m-d H:i:s'),
                            'updated_at' => optional($especialista->updated_at)?->format('Y-m-d H:i:s'),
                            'deleted_at' => optional($especialista->deleted_at)?->format('Y-m-d H:i:s'),

                            'usuario' => $usuarioEspecialista ? [
                                'id' => $usuarioEspecialista->id,
                                'nombre' => $usuarioEspecialista->nombre,
                                'apellidos' => $usuarioEspecialista->apellidos,
                                'dni_usuario' => $usuarioEspecialista->dni_usuario,
                                'email' => $usuarioEspecialista->email,
                                'email_verified_at' => optional($usuarioEspecialista->email_verified_at)?->format('Y-m-d H:i:s'),
                                'direccion' => $usuarioEspecialista->direccion,
                                'fecha_nacimiento' => optional($usuarioEspecialista->fecha_nacimiento)?->format('Y-m-d'),
                                'telefono' => $usuarioEspecialista->telefono,
                                'created_at' => optional($usuarioEspecialista->created_at)?->format('Y-m-d H:i:s'),
                                'updated_at' => optional($usuarioEspecialista->updated_at)?->format('Y-m-d H:i:s'),
                                'deleted_at' => optional($usuarioEspecialista->deleted_at)?->format('Y-m-d H:i:s'),
                            ] : null,
                        ] : null,
                    ] : null,

                    'usuario' => $paciente->user ? [
                        'id' => $paciente->user->id,
                        'nombre' => $paciente->user->nombre,
                        'apellidos' => $paciente->user->apellidos,
                        'dni_usuario' => $paciente->user->dni_usuario,
                        'email' => $paciente->user->email,
                        'email_verified_at' => optional($paciente->user->email_verified_at)?->format('Y-m-d H:i:s'),
                        'direccion' => $paciente->user->direccion,
                        'fecha_nacimiento' => optional($paciente->user->fecha_nacimiento)?->format('Y-m-d'),
                        'telefono' => $paciente->user->telefono,
                        'created_at' => optional($paciente->user->created_at)?->format('Y-m-d H:i:s'),
                        'updated_at' => optional($paciente->user->updated_at)?->format('Y-m-d H:i:s'),
                        'deleted_at' => optional($paciente->user->deleted_at)?->format('Y-m-d H:i:s'),
                    ] : null,
                ];
            });

            $respuesta = $resultado;
            $this->registrarLog($userId, 'listar_pacientes_con_especialista', 'paciente', null);

        } catch (\Throwable $e) {
            $this->logError($userId, 'Error al obtener pacientes con especialista: ' . $e->getMessage(), null);
            $respuesta = ['message' => 'Error interno al obtener pacientes con especialista'];
            $codigo = 500;
        }

        return response()->json($respuesta, $codigo ?? 200);
    }


    /**
     * Actualiza datos del paciente, requiere confirmar password.
     *
     * @param Request $request
     * @param int $idPaciente
     * @return \Illuminate\Http\JsonResponse
     */
    public function actualizarPaciente(Request $request, int $idPaciente): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];

        try {
            $paciente = Paciente::with('user')->findOrFail($idPaciente);

            if (auth()->user()->id !== $paciente->user_id) {
                $codigo = 403;
                $respuesta = ['error' => 'No autorizado'];
                $this->registrarLog(auth()->id(), 'actualizar_paciente_no_autorizado', 'paciente', $idPaciente);
            } else {
                $validar = Validator::make($request->all(), [
                    'nombre' => 'required|string|max:255',
                    'apellidos' => 'required|string|max:255',
                    'dni_usuario' => 'required|string|max:20|unique:users,dni_usuario,' . $paciente->user_id,
                    'email' => 'required|email|max:255|unique:users,email,' . $paciente->user_id,
                    'direccion' => 'nullable|string|max:255',
                    'fecha_nacimiento' => 'nullable|date',
                    'telefono' => 'nullable|string|max:20',
                    'password_actual' => 'required|string',
                ]);

                if ($validar->fails()) {
                    $codigo = 422;
                    $respuesta = ['errors' => $validar->errors()];
                    $this->registrarLog(auth()->id(), 'actualizar_paciente_validacion_fallida', 'paciente', $idPaciente);
                } elseif (!\Hash::check($request->password_actual, $paciente->user->password)) {
                    $codigo = 422;
                    $respuesta = ['error' => 'Contraseña actual incorrecta'];
                    $this->registrarLog(auth()->id(), 'actualizar_paciente_password_incorrecta', 'paciente', $idPaciente);
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

                    $respuesta = [
                        'mensaje' => 'Datos actualizados correctamente',
                        'paciente' => $paciente->load('user'),
                    ];
                    $this->registrarLog(auth()->id(), 'actualizar_paciente_exito', 'paciente', $idPaciente);
                }
            }
        } catch (\Exception $e) {
            $codigo = 404;
            $respuesta = ['error' => 'Paciente no encontrado'];
            $this->logError(auth()->id(), 'Error al actualizar paciente: ' . $e->getMessage(), $idPaciente);
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Cambia la contraseña del paciente.
     *
     * @param Request $request
     * @param int $idPaciente
     * @return \Illuminate\Http\JsonResponse
     */
    public function cambiarPassword(Request $request, int $idPaciente): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];

        try {
            $paciente = Paciente::with('user')->findOrFail($idPaciente);

            if (auth()->user()->id !== $paciente->user_id) {
                $codigo = 403;
                $respuesta = ['error' => 'No autorizado'];
                $this->registrarLog(auth()->id(), 'cambiar_password_no_autorizado', 'paciente', $idPaciente);
            } else {
                $validar = Validator::make($request->all(), [
                    'password_actual' => 'required|string',
                    'password_nuevo' => 'required|string|min:8|confirmed',
                ]);

                if ($validar->fails()) {
                    $codigo = 422;
                    $respuesta = ['errors' => $validar->errors()];
                    $this->registrarLog(auth()->id(), 'cambiar_password_validacion_fallida', 'paciente', $idPaciente);
                } elseif (!\Hash::check($request->password_actual, $paciente->user->password)) {
                    $codigo = 422;
                    $respuesta = ['error' => 'Contraseña actual incorrecta'];
                    $this->registrarLog(auth()->id(), 'cambiar_password_incorrecta', 'paciente', $idPaciente);
                } else {
                    $user = $paciente->user;
                    $user->password = Hash::make($request->password_nuevo);
                    $user->save();

                    $respuesta = ['mensaje' => 'Contraseña actualizada correctamente'];
                    $this->registrarLog(auth()->id(), 'cambiar_password_exito', 'paciente', $idPaciente);
                }
            }
        } catch (\Exception $e) {
            $codigo = 404;
            $respuesta = ['error' => 'Paciente no encontrado'];
            $this->logError(auth()->id(), 'Error al cambiar contraseña: ' . $e->getMessage(), $idPaciente);
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * obtener datos de paciente a partir del id de usuario.
     */

    public function obtenerPacientePorUsuario($userId): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];

        try {
            if (!is_numeric($userId)) {
                $codigo = 400;
                $respuesta = ['message' => 'ID inválido'];
                $this->registrarLog(auth()->id(), 'obtener_paciente_usuario_id_invalido', 'paciente', $userId);
            } else {
                $paciente = Paciente::where('user_id', $userId)->first();

                if (!$paciente) {
                    $codigo = 404;
                    $respuesta = ['message' => 'Paciente no encontrado'];
                    $this->registrarLog(auth()->id(), 'obtener_paciente_usuario_no_encontrado', 'paciente', $userId);
                } else {
                    $respuesta = $paciente;
                    $this->registrarLog(auth()->id(), 'obtener_paciente_usuario_exito', 'paciente', $userId);
                }
            }
        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al obtener paciente'];
            $this->logError(auth()->id(), 'Error obtenerPacientePorUsuario: ' . $e->getMessage(), $userId);
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Almacena un nuevo paciente en la base de datos.
     * Esta función recibe una solicitud con los datos del paciente,
     * valida los datos y crea un nuevo registro en la base de datos.
     * Se maneja la transacción para asegurar que los datos se guarden correctamente
     * y se registran los logs correspondientes.
     *
     * @param  \Illuminate\Http\Request  $solicitud request que contiene los datos del paciente
     * @throws \Illuminate\Validation\ValidationException devuelve una excepción si los datos no cumplen con las reglas de validación.
     * @throws \Exception lanza una excepción si ocurre un error al guardar el paciente.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con un mensaje de éxito o error y el código de respuesta HTTP.
     */
    public function nuevoPaciente(Request $solicitud): JsonResponse
    {
        $respuesta = [];
        $codigo = 201;
        $user = null;

        $validar = Validator::make($solicitud->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validar->fails()) {
            return response()->json(['errors' => $validar->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $user = User::findOrFail($solicitud->user_id);

            $paciente = Paciente::create([
                'user_id' => $user->id,
            ]);

            $user->assignRole('paciente');

            $this->registrarLog(auth()->id(), 'create', "Paciente creado, user_id: {$user->id}", $paciente->id);

            DB::commit();

            $respuesta = [
                'message' => 'Paciente creado correctamente',
                'user' => $user,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError(auth()->id(), 'Error crear paciente: ' . $e->getMessage(), $user?->id);
            $respuesta = ['message' => 'Error interno al crear paciente', 'error' => $e->getMessage()];
            $codigo = 500;
        }

        return response()->json($respuesta, $codigo);
    }

}
