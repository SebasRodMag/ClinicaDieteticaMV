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

        try {
            $citas = Cita::with(['paciente', 'especialista'])->get();

            if ($citas->isEmpty()) {
                $respuesta = ['message' => 'No hay citas registradas'];
            } else {
                $respuesta = ['citas' => $citas];
                $this->registrarLog(auth()->id(), 'listar_citas', 'citas');
            }

        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al obtener las citas'];
            $this->logError(auth()->id(), 'Error al listar citas', $e->getMessage());
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
                'comentarios' => 'nullable|string',
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

            // Mapear a columnas correctas
            $datos = [
                'id_paciente' => $validar['paciente_id'],
                'id_especialista' => $validar['especialista_id'],
                'fecha_hora_cita' => $validar['fecha_hora_cita'],
                'tipo_cita' => $validar['tipo_cita'],
                'comentarios' => $validar['comentarios'] ?? null,
                'estado' => 'pendiente',
            ];

            $cita = Cita::create($datos);

            $this->registrarLog($userId, 'crear_cita', 'citas', $cita->id);

            $respuesta = [
                'message' => 'Cita creada correctamente',
                'cita' => $cita,
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
            $cita = Cita::find($id);

            if (!$cita) {
                $codigo = 404;
                $respuesta = ['message' => 'Cita no encontrada'];
            } else {
                $datos = $solicitud->validate([
                    'fecha_hora_cita' => 'nullable|date_format:Y-m-d H:i:s|after:now',
                    'tipo_cita' => 'nullable|string|max:50',
                    'estado' => 'nullable|string|in:pendiente,confirmada,cancelada,finalizada',
                    'comentarios' => 'nullable|string',
                ], [
                    'fecha_hora_cita.after' => 'La fecha debe ser posterior al momento actual.',
                    'estado.in' => 'El estado debe ser uno de: pendiente, confirmada, cancelada o finalizada.',
                ]);

                $cita->update($datos);

                $this->registrarLog(auth()->id(), 'actualizar_cita', 'citas', $id);

                $respuesta = [
                    'message' => 'Cita actualizada correctamente',
                    'cita' => $cita,
                ];
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $codigo = 422;
            $respuesta = [
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors(),
            ];
        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al actualizar la cita'];
            $this->logError(auth()->id(), 'Error al actualizar cita', [
                'id_cita' => $id,
                'error' => $e->getMessage(),
            ]);
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
            $respuesta = ['message' => 'Cita cancelada correctamente', 'id_cita' => $cita->id];

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
                    return response()->json(['message' => 'Perfil de paciente no encontrado.'], 404);
                }

                $citas = Cita::with(['especialista.user'])
                    ->where('id_paciente', $paciente->id)
                    ->get()
                    ->map(function ($cita) {
                        return [
                            'id' => $cita->id_cita,
                            'fecha' => $cita->fecha_hora_cita->format('Y-m-d'),
                            'hora' => $cita->fecha_hora_cita->format('H:i'),
                            'especialidad' => $cita->especialista->especialidad ?? null,
                            'nombre_especialista' => $cita->especialista->user->nombre . ' ' . $cita->especialista->user->apellidos,
                            'estado' => $cita->estado,
                            'tipo_cita' => $cita->tipo_cita,
                        ];
                    });

                $this->registrarLog($userId, 'listar_mis_citas', 'citas', null);
                $respuesta = ['citas' => $citas];

            } elseif ($rol === 'especialista') {
                $especialista = Especialista::where('user_id', $userId)->first();

                if (!$especialista) {
                    $this->registrarLog($userId, 'listar_mis_citas_fallido', 'especialistas', $userId);
                    return response()->json(['message' => 'Perfil de especialista no encontrado.'], 404);
                }

                $citas = Cita::with(['paciente.user'])
                    ->where('id_especialista', $especialista->id)
                    ->get()
                    ->map(function ($cita) {
                        return [
                            'id' => $cita->id_cita,
                            'fecha' => $cita->fecha_hora_cita->format('Y-m-d'),
                            'hora' => $cita->fecha_hora_cita->format('H:i'),
                            'nombre_paciente' => $cita->paciente->user->nombre . ' ' . $cita->paciente->user->apellidos,
                            'dni_paciente' => $cita->paciente->user->dni_usuario,
                            'estado' => $cita->estado,
                            'tipo_cita' => $cita->tipo_cita,
                            'es_primera' => $cita->es_primera,
                        ];
                    });

                $this->registrarLog($userId, 'listar_mis_citas', 'citas', null);
                $respuesta = ['citas' => $citas];

            } else {
                $this->registrarLog($userId, 'listar_mis_citas_no_autorizado', 'users', $userId);
                return response()->json(['message' => 'No autorizado para ver citas'], 403);
            }

        } catch (\Exception $e) {
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
     * Crea una nueva cita para un paciente, validando horarios y disponibilidad.
     *
     * @param Request $solicitud
     * @param int $idPaciente
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception devuelve error en caso de error en la base de datos
     */
    public function crearNuevaCita(Request $solicitud, int $idPaciente): JsonResponse
    {
        $userId = auth()->id();

        try {
            $paciente = Paciente::findOrFail($idPaciente);

            //Verificar que el usuario autenticado corresponde al paciente
            if ($userId !== $paciente->user_id) {
                $this->registrarLog($userId, 'crear_cita_no_autorizado', 'pacientes', $idPaciente);
                return response()->json(['error' => 'No autorizado'], 403);
            }

            //Validar datos de entrada
            $validator = Validator::make($solicitud->all(), [
                'id_especialista' => 'required|exists:especialistas,id',
                'fecha_hora_cita' => 'required|date_format:Y-m-d H:i:s',
                'tipo_cita' => 'required|in:presencial,telemática',
                'comentario' => 'nullable|string|max:255',
                'es_primera' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                $this->registrarLog($userId, 'crear_cita_validacion_fallida', 'pacientes', $idPaciente);
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $fechaHora = Carbon::parse($solicitud->fecha_hora_cita);

            //Validaciones adicionales
            if ($this->esFinDeSemana($fechaHora) || $this->esFestivo($fechaHora)) {
                $this->registrarLog($userId, 'crear_cita_fecha_no_valida', 'pacientes', $idPaciente);
                return response()->json(['error' => 'La fecha es fin de semana o festivo'], 422);
            }

            if (!$this->esHoraValida($fechaHora)) {
                $this->registrarLog($userId, 'crear_cita_hora_no_valida', 'pacientes', $idPaciente);
                return response()->json(['error' => 'La hora no está dentro de los bloques permitidos'], 422);
            }

            if ($this->existeCitaEnHorario($solicitud->id_especialista, $fechaHora)) {
                $this->registrarLog($userId, 'crear_cita_horario_ocupado', 'pacientes', $idPaciente);
                return response()->json(['error' => 'Ya existe una cita en ese horario para este especialista'], 422);
            }

            //Crear cita
            $cita = new Cita();
            $cita->id_paciente = $idPaciente;
            $cita->id_especialista = $solicitud->id_especialista;
            $cita->fecha_hora_cita = $fechaHora;
            $cita->tipo_cita = $solicitud->tipo_cita;
            $cita->comentario = $solicitud->comentario ?? null;
            $cita->es_primera = $solicitud->es_primera;
            $cita->estado = 'pendiente';
            $cita->save();

            $this->registrarLog($userId, 'crear_cita_exitosa', 'citas', $cita->id);

            return response()->json([
                'mensaje' => 'Cita creada correctamente',
                'cita' => $cita
            ], 201);

        } catch (\Exception $e) {
            $this->logError($userId, 'crear_cita_error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error interno al crear la cita'], 500);
        }
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
     * Método para listar las horas disponibles de un especialista para un día determinado.
     * Se valida la fecha recibida y se verifica que no sea fin de semana ni festivo.
     * Luego se obtiene el horario laboral configurado y la duración estándar de la cita.
     * Se descartan los horarios ya ocupados por citas pendientes del especialista.
     * 
     * @param Request $solicitud Debe incluir campo 'fecha' en formato 'Y-m-d'.
     * @param int $idEspecialista Identificador del especialista.
     * @return \Illuminate\Http\JsonResponse JSON con las horas disponibles.
     */
    public function horasDisponibles(Request $solicitud, int $idEspecialista): JsonResponse
    {
        $solicitud->validate([
            'fecha' => 'required|date_format:Y-m-d',
        ]);

        //Se parsea la fecha al inicio del día para comparaciones
        $fecha = Carbon::createFromFormat('Y-m-d', $solicitud->input('fecha'))->startOfDay();

        // Comprobar si la fecha es fin de semana
        if ($fecha->isWeekend()) {
            return response()->json(['horas_disponibles' => []]);
        }

        //Se obtienen los días no laborables (festivos) desde configuración en JSON
        $festivosJson = Configuracion::where('clave', 'dias_no_laborables')->value('valor');
        $festivos = json_decode($festivosJson, true) ?? [];

        //Se verifica si la fecha está en días no laborables
        if (in_array($fecha->toDateString(), $festivos)) {
            return response()->json(['horas_disponibles' => []]);
        }

        //Se obtiene horario laboral y duración de cita desde configuración
        $horarioJson = Configuracion::where('clave', 'horario_laboral')->value('valor');
        $horario = json_decode($horarioJson, true) ?? [];

        if (!isset($horario['apertura'], $horario['cierre'])) {
            //Si no se encuentra, devuelve un array vacío
            return response()->json(['horas_disponibles' => []]);
        }

        //La duración en minutos, valor por defecto 30 si no está configurado o es inválido
        $duracion = (int) Configuracion::where('clave', 'duracion_cita')->value('valor') ?: 30;

        //Se parcel la hora a un objetos Carbon para inicio y fin de la jornada
        $horaInicio = Carbon::createFromTimeString($horario['apertura']);
        $horaFin = Carbon::createFromTimeString($horario['cierre']);

        //Se obtienen las horas ocupadas por citas pendientes del especialista en la fecha indicada
        $citasOcupadas = Cita::where('id_especialista', $idEspecialista)
            ->whereDate('fecha_hora_cita', $fecha)
            ->where('estado', 'pendiente')
            ->pluck('fecha_hora_cita')
            ->map(fn($fechaHora) => Carbon::parse($fechaHora)->format('H:i'));

        //Bloques de horarios disponibles
        $horaActual = $fecha->copy()->setTimeFromTimeString($horaInicio->format('H:i'));
        $horaLimite = $fecha->copy()->setTimeFromTimeString($horaFin->format('H:i'));

        $disponibles = [];

        while ($horaActual < $horaLimite) {
            $horaStr = $horaActual->format('H:i');
            //Se añade solo si no está ocupada
            if (!$citasOcupadas->contains($horaStr)) {
                $disponibles[] = $horaStr;
            }
            $horaActual->addMinutes($duracion);
        }

        return response()->json(['horas_disponibles' => $disponibles]);
    }
    // --- Funciones auxiliares privadas ---

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
     *
     * @param Carbon $fechaHora
     * @return bool
     */
    private function esHoraValida(Carbon $fechaHora): bool
    {
        try {
            $configuracion = Configuracion::where('clave', 'horario_laboral')->first();

            if (!$configuracion) {
                Log::warning('Configuración horario_laboral no encontrada');
                return false;
            }

            $horario = json_decode($configuracion->valor, true);

            if (!is_array($horario) || !isset($horario['apertura'], $horario['cierre'])) {
                Log::warning('Valor de horario_laboral no es válido');
                return false;
            }

            $horaInicio = Carbon::createFromFormat('H:i', $horario['apertura']);
            $horaFin = Carbon::createFromFormat('H:i', $horario['cierre']);

            // Validar si la hora está dentro del rango y en múltiplos de 30 minutos
            $esMinutoValido = in_array($fechaHora->minute, [0, 30]);
            $esHoraDentroRango = $fechaHora->between($horaInicio, $horaFin, false); // false = no inclusivo

            return $esMinutoValido && $esHoraDentroRango;
        } catch (\Exception $e) {
            Log::error('Error al validar horario laboral: ' . $e->getMessage());
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




}
