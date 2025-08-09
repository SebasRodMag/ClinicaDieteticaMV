<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paciente;
use App\Models\Especialista;
use App\Models\Cita;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Log;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class CitaController extends Controller
{
    use Loggable;

    /**
     * Función para listar todas las citas.
     * Esta función obtiene todas las citas de la base de datos, incluyendo la información del paciente y del especialista.
     * Registra un log de la acción realizada.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el listado de citas.
     * @throws \Exception controla los errores en caso de que falle la consulta en la base de datos
     */
    public function listarCitas(): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];
        $userId = auth()->id();

        try {
            $citas = Cita::with(['paciente.user', 'especialista.user'])->get();

            if ($citas->isEmpty()) {
                $respuesta = ['message' => 'No hay citas registradas'];
            } else {
                $respuesta['citas'] = $citas->map(function ($cita) {
                    if (!$cita->paciente || !$cita->paciente->user) {
                        Log::warning('Cita con id ' . $cita->id_cita . ' tiene paciente no válido');
                    }

                    if (!$cita->especialista || !$cita->especialista->user) {
                        Log::warning('Cita con id ' . $cita->id_cita . ' tiene especialista no válido');
                    }

                    return [
                        'id_cita' => $cita->id_cita,
                        'id_paciente' => $cita->id_paciente,
                        'id_especialista' => $cita->id_especialista,
                        'fecha' => \Carbon\Carbon::parse($cita->fecha_hora_cita)->format('Y-m-d'),
                        'hora' => \Carbon\Carbon::parse($cita->fecha_hora_cita)->format('H:i'),
                        'tipo_cita' => $cita->tipo_cita,
                        'estado' => $cita->estado,
                        'nombre_paciente' => $cita->paciente && $cita->paciente->user
                            ? $cita->paciente->user->nombre . ' ' . $cita->paciente->user->apellidos
                            : 'Paciente no asignado',
                        'nombre_especialista' => $cita->especialista && $cita->especialista->user
                            ? $cita->especialista->user->nombre . ' ' . $cita->especialista->user->apellidos
                            : 'Especialista no asignado',
                        'especialidad' => optional($cita->especialista)->especialidad,
                        'comentario' => $cita->comentario,
                    ];
                });

                if ($userId) {
                    $this->registrarLog($userId, 'listar_citas', 'citas');
                }
            }

        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al obtener las citas'];
            $this->logError($userId, 'Error al listar citas', $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Muestra los detalles de una cita específica por su ID.
     * Incluye información del paciente y del especialista.
     * Registra el acceso si la cita existe.
     *
     * @param int $id ID de la cita a consultar.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con los detalles de la cita o un mensaje de error si no se encuentra.
     */
    public function verCita(int $id): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;

        try {
            $cita = Cita::with(['paciente', 'especialista'])->find($id);

            if (!$cita) {
                $respuesta = ['message' => 'Cita no encontrada'];
                $codigo = 404;
            } else {
                $respuesta = ['cita' => $cita];
                $this->registrarLog(auth()->id(), 'ver_cita', 'citas', $id);
            }

        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al obtener la cita.'];
            $this->logError(auth()->id(), 'Error al ver cita', [
                'id_cita' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Función para crear una nueva cita.
     * Esta función recibe los datos de la cita a través de una solicitud y crea una nueva entrada en la base de datos.
     * Registra un log de la acción realizada.
     * Valida los datos de entrada y maneja posibles errores durante la creación.
     * Si no se proporciona el ID del paciente o especialista, se infiere del usuario autenticado.
     * 
     * @param \Illuminate\Http\Request $solicitud contiene los datos de la cita a crear.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el estado de la operación y los detalles de la cita creada.
     * @throws \Illuminate\Validation\ValidationException lanza una excepción si los datos no cumplen con las reglas de validación.
     * @throws \Exception lanza una excepción si ocurre un error al crear la cita.
     */

    public function nuevaCita(Request $solicitud): JsonResponse
    {
        $codigo = 201;
        $respuesta = [];
        $userId = auth()->id();

        try {
            $validar = $solicitud->validate([
                'paciente_id' => 'sometimes|exists:pacientes,id',
                'especialista_id' => 'sometimes|exists:especialistas,id',
                'fecha_hora_cita' => 'required|date_format:Y-m-d H:i:s|after:now',
                'tipo_cita' => 'required|string|max:50',
                'comentario' => 'nullable|string',
            ], [
                'fecha_hora_cita.required' => 'La fecha y hora son obligatorias.',
                'fecha_hora_cita.date_format' => 'El formato debe ser YYYY-MM-DD HH:MM:SS.',
                'fecha_hora_cita.after' => 'La fecha debe ser posterior a la actual.',
                'tipo_cita.required' => 'El tipo de cita es obligatorio.',
            ]);

            // Resolver paciente si no viene en la solicitud
            if (empty($validar['paciente_id'])) {
                $paciente = Paciente::where('user_id', $userId)->first();

                if (!$paciente) {
                    $codigo = 404;
                    $respuesta = ['message' => 'Paciente no encontrado para el usuario autenticado'];
                    $this->logError($userId, 'Paciente no encontrado al crear cita');
                    return response()->json($respuesta, $codigo);
                }

                $validar['paciente_id'] = $paciente->id;
            }

            // Resolver especialista si no viene en la solicitud
            if (empty($validar['especialista_id'])) {
                $especialista = Especialista::where('user_id', $userId)->first();

                if (!$especialista) {
                    $codigo = 404;
                    $respuesta = ['message' => 'Especialista no encontrado para el usuario autenticado'];
                    $this->logError($userId, 'Especialista no encontrado al crear cita');
                    return response()->json($respuesta, $codigo);
                }

                $validar['especialista_id'] = $especialista->id;
            }

            // Validaciones adicionales
            $fechaHora = Carbon::parse($validar['fecha_hora_cita']);
            $idEspecialista = $validar['especialista_id'];

            if ($this->esFinDeSemana($fechaHora)) {
                return response()->json(['message' => 'No se pueden programar citas en fin de semana.'], 422);
            }

            if ($this->esFestivo($fechaHora)) {
                return response()->json(['message' => 'La fecha seleccionada es un día festivo.'], 422);
            }

            if (!$this->esHoraValida($fechaHora)) {
                return response()->json(['message' => 'La hora seleccionada no está dentro del horario permitido.'], 422);
            }

            if ($this->existeCitaEnHorario($idEspecialista, $fechaHora)) {
                return response()->json(['message' => 'Ya existe una cita para ese especialista en ese horario.'], 422);
            }

            // Mapear a columnas correctas
            $datos = [
                'id_paciente' => $validar['paciente_id'],
                'id_especialista' => $validar['especialista_id'],
                'fecha_hora_cita' => $validar['fecha_hora_cita'],
                'tipo_cita' => $validar['tipo_cita'],
                'comentario' => $validar['comentario'] ?? null,
                'estado' => 'pendiente',
            ];

            $cita = Cita::create($datos);

            if ($cita->tipo_cita === 'telemática') {
                $cita->nombre_sala = $this->generarNombreSala($cita->id_cita);
                $cita->save();
            }

            $this->registrarLog($userId, 'crear_cita', 'citas', $cita->id_cita);

            //Se mapean y formatean los campos para que coincidan con los esperados por el frontend
            $respuesta = [
                'message' => 'Cita creada correctamente',
                'cita' => [
                    'id' => $cita->id_cita,
                    'fecha' => optional($cita->fecha_hora_cita)->format('d-m-Y'),
                    'hora' => optional($cita->fecha_hora_cita)->format('H:i'),
                    'nombre_paciente' => $cita->paciente?->usuario?->nombreCompleto() ?? '',
                    'dni_paciente' => $cita->paciente?->usuario?->dni_usuario ?? '',
                    'estado' => $cita->estado,
                    'tipo_cita' => $cita->tipo_cita,
                    'es_primera' => (bool) $cita->es_primera,
                ],
            ];

        } catch (\Illuminate\Validation\ValidationException $e) {
            $codigo = 422;
            $respuesta = [
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors(),
            ];
        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al crear la cita'];
            $this->logError($userId, 'Error al crear cita', $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }



    /**
     * Función para actualizar una cita existente.
     * Valida los datos y aplica los cambios si la cita existe.
     * Registra un log de la acción realizada.
     * Valida los datos de entrada y maneja posibles errores durante la actualización.
     * 
     * @param \Illuminate\Http\Request $solicitud contiene los datos actualizados de la cita.
     * @param int $id ID de la cita a actualizar.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el estado de la operación y los detalles de la cita actualizada o un mensaje de error si no se encuentra.
     */
    public function actualizarCita(Request $solicitud, int $id): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];

        try {
            $usuario = auth()->user();
            $cita = Cita::find($id);

            if (!$cita) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            //Si la cita ya fue realizada, no se puede modificar.
            if ($cita->estado === 'realizada') {
                return response()->json(['message' => 'No se puede modificar una cita ya realizada.'], 403);
            }

            Log::info('Datos recibidos para actualizar cita:', $solicitud->all());

            //Forzar los tipos a integer ya que dependiendo de donde obtengo el dato en el frontend puede venir como string.
            $solicitud->merge([
                'id_paciente' => $solicitud->has('id_paciente') ? (int) $solicitud->input('id_paciente') : null,
                'id_especialista' => $solicitud->has('id_especialista') ? (int) $solicitud->input('id_especialista') : null,
            ]);

            $datos = $solicitud->validate([
                'id_paciente' => 'nullable|integer|exists:pacientes,id',
                'id_especialista' => 'nullable|integer|exists:especialistas,id',
                'fecha_hora_cita' => 'nullable|date_format:Y-m-d H:i:s',
                'tipo_cita' => 'nullable|string|max:50',
                'estado' => 'nullable|string|in:pendiente,realizada,cancelada,ausente,reprogramada,reasignada',
                'comentario' => 'nullable|string',
            ]);

            $fechaActual = now();
            $esCitaPasada = $cita->fecha_hora_cita < $fechaActual;

            $esAdmin = $usuario->hasRole('administrador');
            $esEspecialista = $usuario->hasRole('especialista');
            $esPaciente = $usuario->hasRole('paciente');

            if ($esPaciente) {
                $nuevaFecha = isset($datos['fecha_hora_cita']) ? Carbon::createFromFormat('Y-m-d H:i:s', $datos['fecha_hora_cita']) : null;
                if ($esCitaPasada || !$nuevaFecha || $fechaActual->diffInHours($nuevaFecha, false) < 24) {
                    return response()->json(['message' => 'No tienes permisos para modificar esta cita.'], 403);
                }
                $cita->update($datos);

            } elseif ($esAdmin || ($esEspecialista && $usuario->id === $cita->id_especialista)) {
                if ($esCitaPasada) {
                    $camposPermitidos = ['fecha_hora_cita', 'estado', 'comentario'];
                    $datosFiltrados = array_filter(
                        $datos,
                        fn($key) => in_array($key, $camposPermitidos),
                        ARRAY_FILTER_USE_KEY
                    );
                    $cita->update($datosFiltrados);

                } else {
                    if (isset($datos['fecha_hora_cita'])) {
                        $nuevaFecha = Carbon::createFromFormat('Y-m-d H:i:s', $datos['fecha_hora_cita']);
                        if ($nuevaFecha < $fechaActual) {
                            return response()->json(['message' => 'La nueva fecha debe ser posterior al momento actual.'], 422);
                        }
                    }
                    $cita->update($datos);
                }

            } else {
                return response()->json(['message' => 'No tienes permisos para modificar esta cita.'], 403);
            }

            $this->registrarLog(
                $usuario->id,
                'actualizar_cita:' .
                'estado_anterior:' . $cita->getOriginal('estado') .
                ' estado_nuevo:' . ($datos['estado'] ?? $cita->estado) .
                ' modificado_por:' . $usuario->getRoleNames()->first(),
                'citas',
                $id,
            );

            return response()->json([
                'message' => 'Cita actualizada correctamente.',
                'cita' => $cita,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validación fallida al actualizar cita:', $e->errors());

            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Excepción al actualizar cita', [
                'user_id' => auth()->id(),
                'cita_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->logError(auth()->id(), 'Error al actualizar cita', [
                'id_cita' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Error interno al actualizar la cita.'], 500);
        }
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

    public function borrarCita(int $id): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];
        $user = auth()->user();

        try {
            if (!$user->hasRole('administrador')) {
                $codigo = 403;
                $respuesta = ['message' => 'No autorizado para eliminar citas'];
                $this->registrarLog($user->id, 'borrar_cita_no_autorizado', 'citas', $id);
                return response()->json($respuesta, $codigo);
            }

            $cita = Cita::find($id);

            if (!$cita) {
                $codigo = 404;
                $respuesta = ['message' => 'Cita no encontrada'];
                $this->registrarLog($user->id, 'borrar_cita_fallido', 'citas', $id);
            } else {
                $cita->delete();
                $respuesta = ['message' => 'Cita eliminada correctamente'];
                $this->registrarLog($user->id, 'borrar_cita', 'citas', $id);
            }
        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al intentar eliminar la cita'];
            $this->logError($user->id, 'Error al eliminar cita', [
                'id_cita' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json($respuesta, $codigo);
    }



    /**
     * Función para cancelar una cita.
     * Permite a un paciente o especialista cancelar una cita a la que estén asociados.
     * Valida la autorización y el estado de la cita antes de cambiar su estado a 'cancelada'.
     *
     * @param int $id ID de la cita a cancelar.
     * @return \Illuminate\Http\JsonResponse devuelve un JSON con el resultado de la operación.
     * @throws \Exception si la cita no existe o si el usuario no tiene permisos para cancelarla.
     */
    public function cancelarCita(int $id): JsonResponse
    {
        $user = auth()->user();
        $userId = $user->id;
        $codigo = 200;
        $respuesta = [];
        $autorizado = false;

        try {
            $cita = Cita::find($id);

            if (!$cita) {
                $this->registrarLog($userId, 'cancelar_cita_fallida', 'citas', $id);
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $rol = $user->getRoleNames()->first();

            if ($rol === 'paciente') {
                $paciente = Paciente::where('user_id', $userId)->first();
                if (!$paciente || $paciente->id !== $cita->id_paciente) {
                    $this->registrarLog($userId, 'cancelar_cita_no_autorizado', 'citas', $id);
                    return response()->json(['message' => 'No autorizado para cancelar esta cita'], 403);
                }
                $autorizado = true;
            } elseif ($rol === 'especialista') {
                $especialista = Especialista::where('user_id', $userId)->first();
                if (!$especialista || $especialista->id !== $cita->id_especialista) {
                    $this->registrarLog($userId, 'cancelar_cita_no_autorizado', 'citas', $id);
                    return response()->json(['message' => 'No autorizado para cancelar esta cita'], 403);
                }
                $autorizado = true;
            }

            if (!$autorizado) {
                return response()->json(['message' => 'No autorizado para cancelar esta cita'], 403);
            }

            if (in_array($cita->estado, ['cancelada', 'realizada'])) {
                $this->registrarLog($userId, 'cancelar_cita_estado_no_cancelable', 'citas', $id);
                return response()->json(['message' => 'La cita no se puede cancelar en su estado actual'], 400);
            }

            $cita->estado = 'cancelada';
            $cita->save();

            $this->registrarLog($userId, 'cancelar_cita', 'citas', $id);
            $respuesta = ['message' => 'Cita cancelada correctamente', 'id_cita' => $cita->id_cita];

        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al cancelar la cita'];
            $this->logError($userId, 'Error al cancelar cita', [
                'cita_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Lista las citas del usuario autenticado, ya sea paciente o especialista.
     *
     * Esta función recupera las citas de un usuario basándose en su rol (paciente o especialista).
     * Carga las relaciones necesarias (especialista.user o paciente.user) para evitar el problema N+1.
     *
     * @return \Illuminate\Http\JsonResponse Una respuesta JSON que contiene una colección de citas
     * formateadas o un mensaje de error si el usuario no tiene
     * el rol adecuado o si el perfil (paciente/especialista) no se encuentra.
     * @throws Exception en caso de error en la base de datos
     */
    public function listarMisCitas(): JsonResponse
    {
        $user = auth()->user();
        $userId = $user->id;
        $rol = $user->getRoleNames()->first();
        $respuesta = [];
        $codigo = 200;

        try {
            if ($rol === 'paciente') {
                $paciente = Paciente::where('user_id', $userId)->first();

                if (!$paciente) {
                    $this->registrarLog($userId, 'listar_mis_citas_fallido', 'pacientes', $userId);
                    $respuesta = ['citas' => [], 'message' => 'Este usuario aún no está vinculado como paciente.'];
                } else {
                    $citas = Cita::with(['especialista.user'])
                        ->where('id_paciente', $paciente->id)
                        ->get()
                        ->map(function ($cita) {
                            return [
                                'id' => $cita->id_cita,
                                'fecha' => $cita->fecha_hora_cita->format('d-m-Y'),
                                'hora' => $cita->fecha_hora_cita->format('H:i'),
                                'especialidad' => $cita->especialista->especialidad ?? null,
                                'nombre_especialista' => optional($cita->especialista?->user)->nombre . ' ' . optional($cita->especialista?->user)->apellidos,
                                'estado' => $cita->estado,
                                'tipo_cita' => $cita->tipo_cita,
                            ];
                        });

                    $respuesta = [
                        'citas' => $citas,
                        'message' => $citas->isEmpty() ? 'No hay citas registradas para este paciente.' : null
                    ];

                    $this->registrarLog($userId, 'listar_mis_citas', 'citas');
                }

            } elseif ($rol === 'especialista') {
                $especialista = Especialista::where('user_id', $userId)->first();

                if (!$especialista) {
                    $this->registrarLog($userId, 'listar_mis_citas_fallido', 'especialistas', $userId);
                    $respuesta = ['citas' => [], 'message' => 'Este usuario aún no está vinculado como especialista.'];
                } else {
                    $citas = Cita::with(['paciente.user'])
                        ->where('id_especialista', $especialista->id)
                        ->get()
                        ->map(function ($cita) {
                            return [
                                'id' => $cita->id_cita,
                                'fecha' => $cita->fecha_hora_cita->format('d-m-Y'),
                                'hora' => $cita->fecha_hora_cita->format('H:i'),
                                'nombre_paciente' => optional($cita->paciente?->user)->nombre . ' ' . optional($cita->paciente?->user)->apellidos,
                                'dni_paciente' => optional($cita->paciente?->user)->dni_usuario,
                                'estado' => $cita->estado,
                                'tipo_cita' => $cita->tipo_cita,
                                'es_primera' => $cita->es_primera,
                            ];
                        });

                    $respuesta = [
                        'citas' => $citas,
                        'message' => $citas->isEmpty() ? 'No hay citas asignadas actualmente.' : null
                    ];

                    $this->registrarLog($userId, 'listar_mis_citas', 'citas');
                }

            } else {
                $codigo = 403;
                $respuesta = ['message' => 'No autorizado para ver citas'];
                $this->registrarLog($userId, 'listar_mis_citas_no_autorizado', 'users', $userId);
            }

        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al obtener las citas'];
            $this->logError($userId, 'Error al listar mis citas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Cambia el estado de una cita a 'cancelado' si estaba 'pendiente'.
     *
     * @param int $idCita
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelarCitaPaciente(int $idCita): JsonResponse
    {
        $userId = auth()->id();

        try {
            $cita = Cita::with('paciente')->findOrFail($idCita);

            // Verificar que el usuario autenticado es el paciente asociado
            if ($userId !== $cita->paciente->user_id) {
                $this->registrarLog($userId, 'cancelar_cita_paciente_no_autorizado', 'citas', $idCita);
                return response()->json(['error' => 'No autorizado'], 403);
            }

            if ($cita->estado !== 'pendiente') {
                $this->registrarLog($userId, 'cancelar_cita_estado_invalido', 'citas', $idCita);
                return response()->json(['error' => 'Sólo se pueden cancelar citas pendientes'], 422);
            }

            $cita->estado = 'cancelada';  // Corrijo el estado a 'cancelada' (no 'cancelado')
            $cita->save();

            $this->registrarLog($userId, 'cita_cancelada_correctamente', 'citas', $idCita);

            return response()->json(['mensaje' => 'Cita cancelada correctamente'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->registrarLog($userId, 'cancelar_cita_no_encontrada', 'citas', $idCita);
            return response()->json(['error' => 'Cita no encontrada'], 404);

        } catch (\Exception $e) {
            $this->logError($userId, 'cancelar_cita_error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id_cita' => $idCita
            ]);
            return response()->json(['error' => 'Error interno al cancelar la cita'], 500);
        }
    }

    /**
     * Método para obtener la url de la videoconferencia según el usuario logueado.
     * Se verifica si el usuario es paciente o especialista y si estos pertenecen a la cita,
     * de esta forma, evitamos que se abra una conferencia donde solo hay un usuario asignado.
     * @param $idCita id de la cita
     * @return \Illuminate\Http\JsonResponse devuelve la url de la videoconferencia 
     * y un codigo 200 en caso de éxito o de lo contrario, mensaje de error con su código de error 
     */
    public function obtenerSalaSegura($idCita): JsonResponse
    {
        $user = auth()->user();
        $codigo = 200;
        $respuesta = [];

        try {
            $cita = Cita::findOrFail($idCita);

            //Verificar si el usuario es paciente o especialista de la cita asignado
            $esPaciente = $cita->id_paciente && $cita->paciente->user_id === $user->id;
            $esEspecialista = $cita->id_especialista && $cita->especialista->user_id === $user->id;

            //Si no es uno de los participantes, no podrá tener acceso a la sala
            if (!$esPaciente && !$esEspecialista) {
                $codigo = 403;
                $respuesta = ['message' => 'No tienes permisos para acceder a esta sala'];
                return response()->json($respuesta, $codigo);
            }

            //Validar que sea una cita telemática con sala definida
            if (!$this->esCitaTelematicaValida($cita)) {
                $codigo = 400;
                $respuesta = ['message' => 'La cita no es telemática o no tiene sala asignada'];
                return response()->json($respuesta, $codigo);
            }

            $respuesta = ['nombre_sala' => $cita->nombre_sala];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $codigo = 404;
            $respuesta = ['message' => 'Cita no encontrada'];
        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al obtener el nombre de la sala'];
            $this->logError($user->id, 'Error en obtenerSalaSegura', $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Método para listar las horas disponibles de un especialista para un día determinado.
     * Se valida la fecha recibida y se verifica que no sea fin de semana ni festivo.
     * Si no se pasa el ID del especialista, se intenta obtener del usuario autenticado.
     * Luego se obtiene el horario laboral configurado y la duración estándar de la cita.
     * Se descartan los horarios ya ocupados por citas pendientes del especialista.
     * 
     * @param Request $solicitud Debe incluir campo 'fecha' en formato 'Y-m-d'.
     * @param int $idEspecialista Identificador del especialista.
     * @return \Illuminate\Http\JsonResponse JSON con las horas disponibles.
     */
    private function calcularHorasDisponibles(string $fechaStr, ?int $idEspecialista = null): array
    {
        $respuesta = ['horas_disponibles' => []];

        $fecha = Carbon::createFromFormat('Y-m-d', $fechaStr)->startOfDay();

        if ($fecha->isWeekend() || in_array($fecha->toDateString(), $this->obtenerFestivos())) {
            return $respuesta;
        }

        if (is_null($idEspecialista)) {
            $idEspecialista = $this->obtenerIdEspecialistaDesdeUsuario();
        }

        if (is_null($idEspecialista)) {
            throw new \Exception('ID de especialista no disponible');
        }

        $horario = $this->obtenerHorarioLaboral();

        if (!isset($horario['apertura'], $horario['cierre'])) {
            throw new \Exception('Horario laboral no configurado');
        }

        $duracion = (int) Configuracion::where('clave', 'duracion_cita')->value('valor') ?: 30;
        $horaInicio = Carbon::createFromTimeString($horario['apertura']);
        $horaFin = Carbon::createFromTimeString($horario['cierre']);
        $ocupadas = $this->obtenerHorasOcupadas($idEspecialista, $fecha);

        $horaActual = $fecha->copy()->setTimeFromTimeString($horaInicio->format('H:i'));
        $horaLimite = $fecha->copy()->setTimeFromTimeString($horaFin->format('H:i'));

        while ($horaActual < $horaLimite) {
            $horaStr = $horaActual->format('H:i');
            if (!in_array($horaStr, $ocupadas)) {
                $respuesta['horas_disponibles'][] = $horaStr;
            }
            $horaActual->addMinutes($duracion);
        }

        return $respuesta;
    }

    public function horasDisponiblesEspecialista(string $fecha): JsonResponse
    {
        try {
            $this->validarFecha($fecha);
            $respuesta = $this->calcularHorasDisponibles($fecha, null);
            return response()->json($respuesta, 200);
        } catch (\Exception $e) {
            return response()->json([
                'horas_disponibles' => [],
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function horasDisponiblesPorEspecialista(int $idEspecialista, string $fecha): JsonResponse
    {
        try {
            $this->validarFecha($fecha);
            $respuesta = $this->calcularHorasDisponibles($fecha, $idEspecialista);
            return response()->json($respuesta, 200);
        } catch (\Exception $e) {
            return response()->json([
                'horas_disponibles' => [],
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // --- Funciones auxiliares privadas ---

    private function validarFecha(string $fecha): void
    {
        $validator = \Validator::make(['fecha' => $fecha], [
            'fecha' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            throw new \Exception('Fecha inválida o no proporcionada.');
        }
    }
    /**
     * Verifica si la fecha es un fin de semana.
     *
     * @param Carbon $fecha
     * @return bool
     */
    private function esFinDeSemana(Carbon $fecha): bool
    {
        return $fecha->isWeekend();
    }

    /**
     * Verifica si la fecha es festivo.
     *
     * @param Carbon $fecha
     * @return bool
     */

    private function esFestivo(Carbon $fecha): bool
    {
        try {
            $configuracion = Configuracion::where('clave', 'dias_no_laborables')->first();

            if (!$configuracion) {
                Log::warning('Configuración dias_no_laborables no encontrada');
                $this->registrarLog(auth()->id(), 'Error_Configuracion_dias_no_laborables_no_encontrada', auth()->id());
                return false;
            }

            $diasNoLaborables = json_decode($configuracion->valor, true);

            if (!is_array($diasNoLaborables)) {
                Log::warning('dias_no_laborables no es un array válido');
                return false;
            }

            return in_array($fecha->toDateString(), $diasNoLaborables);
        } catch (\Exception $e) {
            Log::error('Error al consultar dias_no_laborables: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si la hora está en bloques de 30 minutos entre 08:00 y 14:30.
     * Segun la configuración del horario laboral.
     *
     * @param Carbon $fechaHora
     * @return bool
     */
    private function esHoraValida(Carbon $fechaHora): bool
    {
        try {
            $configuracionHorario = Configuracion::where('clave', 'horario_laboral')->first();
            $configuracionDuracion = Configuracion::where('clave', 'duracion_cita')->first();

            if (!$configuracionHorario || !$configuracionDuracion) {
                Log::warning('Configuración incompleta: horario_laboral o duracion_cita no encontrada');
                return false;
            }

            $horario = json_decode($configuracionHorario->valor, true);
            $duracion = (int) $configuracionDuracion->valor;

            if (!is_array($horario) || !isset($horario['apertura'], $horario['cierre']) || $duracion <= 0) {
                Log::warning('Valor de configuración inválido');
                return false;
            }

            // Asignar la misma fecha para asegurar la comparación correcta
            $horaInicio = $fechaHora->copy()->setTimeFromTimeString($horario['apertura']);
            $horaFin = $fechaHora->copy()->setTimeFromTimeString($horario['cierre']);

            // Verificar si la hora está en múltiplos de la duración
            $minutosDesdeInicio = $horaInicio->diffInMinutes($fechaHora, false);

            $esMultiplo = $minutosDesdeInicio >= 0 && $minutosDesdeInicio % $duracion === 0;

            // Validar que la cita completa no exceda el cierre
            $horaFinCita = $fechaHora->copy()->addMinutes($duracion);
            $noExcedeHorario = $horaFinCita->lte($horaFin);

            return $esMultiplo && $noExcedeHorario;
        } catch (\Exception $e) {
            Log::error('Error al validar horario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si ya existe una cita para ese especialista en la fecha y hora dada.
     *
     * @param int $idEspecialista
     * @param Carbon $fechaHora
     * @return bool
     */
    private function existeCitaEnHorario(int $idEspecialista, Carbon $fechaHora): bool
    {
        return Cita::where('id_especialista', $idEspecialista)
            ->where('fecha_hora_cita', $fechaHora)
            ->where('estado', 'pendiente')
            ->exists();
    }


    /**
     * Compartir la configuración principal con el Frontend
     */
    public function configuracion(): JsonResponse
    {
        return response()->json([
            'horario_laboral' => json_decode(Configuracion::where('clave', 'horario_laboral')->value('valor'), true) ?? [],
            'dias_no_laborables' => json_decode(Configuracion::where('clave', 'dias_no_laborables')->value('valor'), true) ?? [],
            'duracion_cita' => (int) Configuracion::where('clave', 'duracion_cita')->value('valor') ?: 30
        ]);
    }

    /**
     * Devuelve los tipos de estado de cita disponibles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tiposEstadoCita(): JsonResponse
    {
        try {
            $tiposEstado = [
                'pendiente',
                'realizada',
                'cancelada',
                'finalizada',
                'ausente',
                'reasignada'
            ];

            return response()->json([
                'success' => true,
                'tipos_estado' => $tiposEstado,
            ]);
        } catch (\Throwable $e) {
            Log::error('[CONFIG] Error al obtener tipos de estado de cita: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno al consultar los tipos de estado de cita.',
                'tipos_estado' => []
            ], 500);
        }
    }

    /**
     * Verifica si una cita es telemática válida (tipo y nombre de sala definidos).
     *
     * @param Cita $cita
     * @return bool
     */
    private function esCitaTelematicaValida(Cita $cita): bool
    {
        return $cita->tipo_cita === 'telemática' && !empty($cita->nombre_sala);
    }

    /**
     * Devuelve el ID del especialista asociado al usuario autenticado.
     */
    private function obtenerIdEspecialistaDesdeUsuario(): ?int
    {
        $especialista = Especialista::where('user_id', auth()->id())->first();
        return $especialista?->id;
    }

    /**
     * Devuelve un array con los días festivos desde la configuración.
     */
    private function obtenerFestivos(): array
    {
        $json = Configuracion::where('clave', 'dias_no_laborables')->value('valor');
        return json_decode($json, true) ?? [];
    }

    /**
     * Devuelve un array con los valores de horario laboral configurado.
     */
    private function obtenerHorarioLaboral(): array
    {
        $json = Configuracion::where('clave', 'horario_laboral')->value('valor');
        return json_decode($json, true) ?? [];
    }

    /**
     * Devuelve las horas ya ocupadas para un especialista y una fecha concreta.
     */
    private function obtenerHorasOcupadas(int $idEspecialista, Carbon $fecha): array
    {
        return Cita::where('id_especialista', $idEspecialista)
            ->whereDate('fecha_hora_cita', $fecha)
            ->where('estado', 'pendiente')
            ->pluck('fecha_hora_cita')
            ->map(fn($dt) => Carbon::parse($dt)->format('H:i'))
            ->toArray();
    }

    /**
     * Función para generar los nombres de las salas para Jetsi Meet
     */
    public function generarNombreSala($idCita): string
    {
        if (!is_numeric($idCita) || $idCita <= 0) {
            throw new \InvalidArgumentException('El ID de la cita no es válido');
        }

        $citaExiste = \App\Models\Cita::where('id_cita', $idCita)->exists();

        if (!$citaExiste) {
            throw new \Exception("La cita con ID $idCita no existe");
        }

        return 'clinicaDietetica-cita-' . $idCita;
    }


    /**
     * Método para cambiar el estado de la cita siempre que el usuario autenticado sea un especialista o paciente y donde, en el caso del paciente, solo puede cancelar la cita
     * @param \Illuminate\Http\Request $request estado al que cambiaremos la cita
     * @param int $id de la cita
     * @return JsonResponse respuesta con el estado de la solicitud
     */
    public function cambiarEstadoCita(Request $request, int $id): JsonResponse
    {
        $usuario = auth()->user();
        $rol = $usuario->getRoleNames()->first();
        $userId = $usuario->id;

        $estadoNuevo = $request->input('estado');

        $request->validate([
            'estado' => 'required|string|in:pendiente,realizada,cancelada,ausente,reasignada,finalizada',
        ]);

        $cita = Cita::find($id);
        if (!$cita) {
            return response()->json(['message' => 'Cita no encontrada.'], 404);
        }

        $puedeActualizar = false;

        if ($rol === 'administrador') {
            $puedeActualizar = true;
        } elseif ($rol === 'especialista') {
            $especialista = Especialista::where('user_id', $userId)->first();
            if ($especialista && $especialista->id === $cita->id_especialista) {
                $puedeActualizar = true;
            }
        } elseif ($rol === 'paciente') {
            $paciente = Paciente::where('user_id', $userId)->first();
            if ($paciente && $paciente->id === $cita->id_paciente && $estadoNuevo === 'cancelada') {
                $puedeActualizar = true;
            }
        }

        if (!$puedeActualizar) {
            return response()->json(['message' => 'No autorizado para cambiar el estado de esta cita.'], 403);
        }

        $estadoAnterior = $cita->estado;
        $cita->estado = $estadoNuevo;
        $cita->save();

        $this->registrarLog($userId, "cambiar_estado_cita ($estadoAnterior → $estadoNuevo)", 'citas', $id);

        return response()->json([
            'message' => 'Estado de la cita actualizado correctamente.',
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
        ]);
    }



}
