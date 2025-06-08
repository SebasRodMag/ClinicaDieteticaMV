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
            'paciente_id'     => 'sometimes|exists:pacientes,id',
            'especialista_id' => 'sometimes|exists:especialistas,id',
            'fecha_hora_cita'      => 'required|date_format:Y-m-d H:i:s|after:now',
            'tipo_cita'            => 'required|string|max:50',
            'comentarios'     => 'nullable|string',
        ]);

        try {
            $userId = auth()->id();

            // Si no se proporciona paciente_id, obtenerlo desde la tabla pacientes según user_id
            if (empty($validar['paciente_id'])) {
                $paciente = Paciente::where('user_id', $userId)->first();
                if (!$paciente) {
                    Log::error("Paciente no encontrado para el usuario autenticado con user_id: $userId");
                    $this->registrarLog($userId, 'crear_cita_error', 'Paciente no encontrado para el usuario autenticado');
                    // Log full exception stack trace for debugging
                    Log::error("Request data: " . json_encode($validar));
                    return response()->json(['message' => 'Paciente no encontrado para el usuario autenticado'], 404);
                }
                $validar['paciente_id'] = $paciente->id;
            }

            // Si no se proporciona especialista_id, obtenerlo desde la tabla especialistas según user_id
            if (empty($validar['especialista_id'])) {
                $especialista = Especialista::where('user_id', $userId)->first();
                if (!$especialista) {
                    return response()->json(['message' => 'Especialista no encontrado para el usuario autenticado'], 404);
                }
                $validar['especialista_id'] = $especialista->id;
            }

            //Las citas tienen el estado 'pendiente' por defecto
            // Map 'paciente_id' and 'especialista_id' to 'id_paciente' and 'id_especialista'
            $datos = $validar;
            if (isset($datos['paciente_id'])) {
                $datos['id_paciente'] = $datos['paciente_id'];
                unset($datos['paciente_id']);
            }
            if (isset($datos['especialista_id'])) {
                $datos['id_especialista'] = $datos['especialista_id'];
                unset($datos['especialista_id']);
            }
            $datos = array_merge($datos, ['estado' => 'pendiente']);
            $cita = Cita::create($datos);

            $this->registrarLog($userId, 'crear_cita', "Cita creada ID {$cita->id}");

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
     * Permite a un paciente o especialista cancelar una cita a la que estén asociados.
     * Valida la autorización y el estado de la cita antes de cambiar su estado a 'cancelada'.
     *
     * @param int $id ID de la cita a cancelar.
     * @return \Illuminate\Http\JsonResponse devuelve un JSON con el resultado de la operación.
     * @throws \Exception si la cita no existe o si el usuario no tiene permisos para cancelarla.
     */
    public function cancelarCita(int $id): JsonResponse
    {
        $userId = auth()->id();
        $rol = auth()->user()->getRoleNames()->first();
        $autorizado = false;

        // Validar que el ID sea numérico válido
        if (!is_numeric($id) || intval($id) <= 0) {
            $this->registrarLog($userId, 'cancelar_cita_error_id_cita_invalido', $userId);
            return response()->json(['message' => 'ID de cita inválido'], 400);
        }

        $cita = Cita::find($id);

        if (!$cita) {
            $this->registrarLog($userId, 'cancelar_cita_error_cita_no_encontrada', $userId);
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $paciente = Paciente::where('user_id', $userId)->first();

        if ($rol === 'paciente') {
            $paciente = Paciente::where('user_id', $userId)->first();
            if (!$paciente || $paciente->id !== $cita->id_paciente) {
                $this->registrarLog($userId, 'cancelar_cita_no_autorizado_paciente', $userId);
                return response()->json(['message' => 'No autorizado: esta cita no pertenece al paciente. id_paciente: '.$paciente->id_paciente.' y el paciente de la cita: '.$cita->id_paciente], 403);
            }

            $autorizado = true;
        } elseif ($rol === 'especialista') {
            $especialista = Especialista::where('id_usuario', $userId)->first();

            if (!$especialista || $especialista->id_especialista !== $cita->id_especialista) {
                $this->registrarLog($userId, 'cancelar_cita_no_autorizado_especialista', $userId);
                return response()->json(['message' => 'No autorizado: esta cita no pertenece al especialista'], 403);
            }

            $autorizado = true;
        }

        if (!$autorizado) {
            return response()->json(['message' => 'No autorizado para cancelar esta cita'], 403);
        }

        if (in_array($cita->estado, ['cancelada', 'realizada'])) {
            $this->registrarLog($userId, 'cancelar_cita_estado_no_cancelable', $cita->id_cita);
            return response()->json(['message' => 'La cita ya no se puede cancelar'], 400);
        }

        try {
            $cita->estado = 'cancelada';
            $cita->save();

            $this->registrarLog($userId, 'cancelar_cita', $cita->id_cita);
            return response()->json(['message' => 'Cita cancelada correctamente', 'id_cita' => $cita->id_cita], 200);
        } catch (\Exception $e) {
            $this->registrarLog($userId, 'cancelar_cita_error: ' . $e->getMessage(), $cita->id_cita);
            return response()->json(['message' => 'Error interno al cancelar la cita'], 500);
        }
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
     */
    public function listarMisCitas()
    {
        try {
            // Obtener el ID del usuario autenticado
            $userId = Auth::id();

            // Obtener el primer rol del usuario autenticado
            // Asegúrate de que el usuario tenga un rol asignado, de lo contrario getRoleNames() podría devolver una colección vacía.
            $rol = Auth::user()->getRoleNames()->first();

            $citas = []; // Inicializar $citas como un array vacío

            if ($rol === 'paciente') {
                // Buscar el paciente asociado al user_id
                // Asegúrate de que el paciente exista antes de intentar acceder a su 'id'
                $paciente = Paciente::where('user_id', $userId)->first();

                if ($paciente) {
                    // Obtener las citas del paciente, cargando eager load las relaciones 'especialista' y 'especialista.user'
                    // Esto evita el problema de N+1 consultas y mejora el rendimiento.
                    $citas = Cita::with(['especialista.user'])
                                ->where('id_paciente', $paciente->id)
                                ->get() // Ejecutar la consulta y obtener la colección de citas
                                ->map(function ($cita) {
                                    // $cita->fecha_hora_cita ya es un objeto Carbon gracias a los casts en el modelo Cita
                                    return [
                                        'id' => $cita->id_cita,
                                        'fecha' => $cita->fecha_hora_cita->format('Y-m-d'),
                                        'hora' => $cita->fecha_hora_cita->format('H:i'),
                                        'especialidad' => $cita->especialista->especialidad,
                                        'nombre_especialista' => $cita->especialista->user->nombre . ' ' . $cita->especialista->user->apellidos,
                                        'estado' => $cita->estado,
                                        'tipo_cita' => $cita->tipo_cita, // Añadido para más detalle
                                    ];
                                });
                } else {
                    // Manejar el caso en que no se encuentre el paciente
                    return response()->json(['message' => 'No se encontró el perfil de paciente para este usuario.'], 404);
                }
            } elseif ($rol === 'especialista') {
                // Buscar el especialista asociado al user_id
                // Asegúrate de que el especialista exista antes de intentar acceder a su 'id'
                $especialista = Especialista::where('user_id', $userId)->first();

                if ($especialista) {
                    // Obtener las citas del especialista, cargando eager load las relaciones 'paciente' y 'paciente.user'
                    // Esto evita el problema de N+1 consultas y mejora el rendimiento.
                    $citas = Cita::with(['paciente.user'])
                                ->where('id_especialista', $especialista->id)
                                ->get() // Ejecutar la consulta y obtener la colección de citas
                                ->map(function ($cita) {
                                    // Para el especialista, mostramos los datos del paciente y la cita
                                    return [
                                        'id' => $cita->id_cita,
                                        'fecha' => $cita->fecha_hora_cita->format('Y-m-d'),
                                        'hora' => $cita->fecha_hora_cita->format('H:i'),
                                        'nombre_paciente' => $cita->paciente->user->nombre . ' ' . $cita->paciente->user->apellidos,
                                        'dni_paciente' => $cita->paciente->user->dni_usuario, // Útil para el especialista
                                        'estado' => $cita->estado,
                                        'tipo_cita' => $cita->tipo_cita,
                                        'es_primera' => $cita->es_primera, // También puede ser útil
                                    ];
                                });
                } else {
                    // Manejar el caso en que no se encuentre el especialista
                    return response()->json(['message' => 'No se encontró el perfil de especialista para este usuario.'], 404);
                }
            } else {
                // Si el usuario no tiene el rol de 'paciente' o 'especialista'
                return response()->json(['message' => 'El usuario no tiene el rol necesario para listar citas.'], 403);
            }

            // Devolver las citas como una respuesta JSON
            return response()->json($citas);

        } catch (\Exception $e) {
            // Capturar cualquier excepción que ocurra durante el proceso
            // Registrar el error para depuración (se guardará en storage/logs/laravel.log)
            Log::error("Error al listar citas: " . $e->getMessage(), ['exception' => $e, 'user_id' => Auth::id()]);

            // Devolver una respuesta de error genérica al cliente
            return response()->json(['message' => 'Ocurrió un error interno al obtener las citas.'], 500);
        }
    }

    /**
     * Crea una nueva cita para un paciente, validando horarios y disponibilidad.
     *
     * @param Request $request
     * @param int $idPaciente
     * @return \Illuminate\Http\JsonResponse
     */
    public function crearNuevaCita(Request $request, int $idPaciente)
    {
        $respuesta = null;

        try {
            $paciente = Paciente::findOrFail($idPaciente);

            if (auth()->user()->id !== $paciente->user_id) {
                $respuesta = response()->json(['error' => 'No autorizado'], 403);
            } else {
                $validator = Validator::make($request->all(), [
                    'id_especialista' => 'required|exists:especialistas,id',
                    'fecha_hora_cita' => 'required|date_format:Y-m-d H:i:s',
                    'tipo_cita' => 'required|in:presencial,telemática',
                    'comentario' => 'nullable|string|max:255',
                    'es_primera' => 'required|boolean',
                ]);

                if ($validator->fails()) {
                    $respuesta = response()->json(['errors' => $validator->errors()], 422);
                } else {
                    $fechaHora = Carbon::parse($request->fecha_hora_cita);

                    if ($this->esFinDeSemana($fechaHora) || $this->esFestivo($fechaHora)) {
                        $respuesta = response()->json(['error' => 'La fecha es fin de semana o festivo'], 422);
                    } elseif (!$this->esHoraValida($fechaHora)) {
                        $respuesta = response()->json(['error' => 'La hora no está dentro de los bloques permitidos'], 422);
                    } elseif ($this->existeCitaEnHorario($request->id_especialista, $fechaHora)) {
                        $respuesta = response()->json(['error' => 'Ya existe una cita en ese horario para este especialista'], 422);
                    } else {
                        $cita = new Cita();
                        $cita->id_paciente = $idPaciente;
                        $cita->id_especialista = $request->id_especialista;
                        $cita->fecha_hora_cita = $fechaHora;
                        $cita->tipo_cita = $request->tipo_cita;
                        $cita->comentario = $request->comentario;
                        $cita->es_primera = $request->es_primera;
                        $cita->estado = 'pendiente';
                        $cita->save();

                        $respuesta = response()->json(['mensaje' => 'Cita creada correctamente', 'cita' => $cita], 201);
                    }
                }
            }
        } catch (\Exception $e) {
            $respuesta = response()->json(['error' => 'Error creando la cita'], 500);
        }

        return $respuesta;
    }

    /**
     * Cambia el estado de una cita a 'cancelado' si estaba 'pendiente'.
     *
     * @param int $idCita
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelarCitaPaciente(int $idCita)
    {
        $respuesta = null;

        try {
            $cita = Cita::findOrFail($idCita);

            $paciente = $cita->paciente;
            if (auth()->user()->id !== $paciente->user_id) {
                $this->registrarLog($userId, 'Error_cancelar_cita_paciente_usuario_no_autorizado', $userId);
                $respuesta = response()->json(['error' => 'No autorizado'], 403);
                
            } elseif ($cita->estado !== 'pendiente') {
                $this->registrarLog($userId, 'Error_cancelar_cita_no_tiene_estado_pendiente', $userId);
                $respuesta = response()->json(['error' => 'Sólo se pueden cancelar citas pendientes'], 422);
            } else {
                $cita->estado = 'cancelado';
                $cita->save();
                $this->registrarLog($userId, 'Cita_cancelada_correctamente', $userId);
                $respuesta = response()->json(['mensaje' => 'Cita cancelada correctamente']);
            }
        } catch (\Exception $e) {
            $respuesta = response()->json(['error' => 'Cita no encontrada'], 404);
            $this->registrarLog($userId, 'Error_al_buscar_cita_no_encontrada', $userId);
        }

        return $respuesta;
    }


    /**
     * Método para listar las horas disponibles de un medico, para un dia determinado
     * Esté método, recibe como parámetro, el id del medico y la fecha de la cita para listar los horarios disponibles
     * para ello, se apoya en las funciones auxiliares, esFinDeSemana(), esFestivo(), esHoraValida().
     */
    public function horasDisponibles(Request $request, int $idEspecialista)
    {
        // Validar que la fecha venga en el formato correcto
        $request->validate([
            'fecha' => 'required|date_format:Y-m-d',
        ]);

        $fecha = Carbon::createFromFormat('Y-m-d', $request->input('fecha'))->startOfDay();

        // Si es fin de semana
        if ($fecha->isWeekend()) {
            return response()->json(['horas_disponibles' => []]);
        }

        // Festivos desde tabla 'configuracion'
        $festivos = json_decode(
            Configuracion::where('clave', 'dias_no_laborables')->value('valor'),
            true
        ) ?? [];

        if (in_array($fecha->toDateString(), $festivos)) {
            return response()->json(['horas_disponibles' => []]);
        }

        // Horario laboral y duración
        $horario = json_decode(
            Configuracion::where('clave', 'horario_laboral')->value('valor'),
            true
        ) ?? [];

        if (!isset($horario['apertura'], $horario['cierre'])) {
            return response()->json(['horas_disponibles' => []]);
        }

        $duracion = (int) Configuracion::where('clave', 'duracion_cita')->value('valor') ?? 30;

        $horaInicio = Carbon::createFromTimeString($horario['apertura']);
        $horaFin = Carbon::createFromTimeString($horario['cierre']);

        // Citas ya ocupadas ese día
        $citasOcupadas = Cita::where('id_especialista', $idEspecialista)
            ->whereDate('fecha_hora_cita', $fecha)
            ->where('estado', 'pendiente')
            ->pluck('fecha_hora_cita')
            ->map(fn($cita) => Carbon::parse($cita)->format('H:i'));

        // Generar los bloques de horario disponibles
        $hora = $fecha->copy()->setTimeFromTimeString($horaInicio->format('H:i'));
        $horaLimite = $fecha->copy()->setTimeFromTimeString($horaFin->format('H:i'));

        $disponibles = [];

        while ($hora < $horaLimite) {
            $horaStr = $hora->format('H:i');
            if (!$citasOcupadas->contains($horaStr)) {
                $disponibles[] = $horaStr;
            }
            $hora->addMinutes($duracion);
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
        $userId = auth()->user()->id;

        try {
            $config = Configuracion::where('clave', 'dias_no_laborables')->first();

            if (!$config) {
                Log::warning('Configuración dias_no_laborables no encontrada');
                $this->registrarLog($userId, 'Error_Configuracion_dias_no_laborables no encontrada', $userId);
                return false;
            }

            $diasNoLaborables = json_decode($config->valor, true);

            if (!is_array($diasNoLaborables)) {
                Log::warning('dias_no_laborables no es un array válido');
                return false;
            }

            $fechaSolo = $fecha->toDateString(); // convierte a 'YYYY-MM-DD'

            $this->registrarLog($userId, 'Se_consulta_Configuracion_dias_no_laborables', $userId);

            return in_array($fechaSolo, $diasNoLaborables);
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
            $config = Configuracion::where('clave', 'horario_laboral')->first();

            if (!$config) {
                Log::warning('Configuración horario_laboral no encontrada');
                return false;
            }

            $horario = json_decode($config->valor, true);

            if (!is_array($horario) || !isset($horario['apertura']) || !isset($horario['cierre'])) {
                Log::warning('Valor de horario_laboral no es válido');
                return false;
            }

            $horaInicio = Carbon::createFromFormat('H:i', $horario['apertura']);
            $horaFin = Carbon::createFromFormat('H:i', $horario['cierre']);

            $esMinutoValido = in_array($fechaHora->minute, [0, 30]);
            $esHoraValida = $fechaHora->between($horaInicio, $horaFin);

            return $esMinutoValido && $esHoraValida;
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
    public function configuracion()
    {
        return response()->json([
            'horario_laboral' => json_decode(Configuracion::where('clave', 'horario_laboral')->value('valor'), true),
            'dias_no_laborables' => json_decode(Configuracion::where('clave', 'dias_no_laborables')->value('valor'), true),
            'duracion_cita' => (int) Configuracion::where('clave', 'duracion_cita')->value('valor')
        ]);
    }

    


}
