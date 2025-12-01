<?php

namespace App\Http\Controllers;

use App\Models\Especialista;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Traits\Loggable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Cita;
use App\Notifications\EspecialistaBajaNotificacion;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class EspecialistaController extends Controller
{
    use Loggable, Notifiable;

    /**
     * Mostrar todos los especialistas. No tiene end-points públicos porque es llamada desde otros métodos.
     * Devolverá una lista de todos los especialistas registrados en la base de datos.
     * @return \Illuminate\Http\JsonResponse devolverá una respuesta JSON con el listado de especialistas o un mensaje de error si no hay especialistas registrados.
     */
    public function listarEspecialistas(Request $solicitud): JsonResponse
    {
        $especialidades = $solicitud->query('especialidades');
        $query = Especialista::query();

        if ($especialidades) {
            $especialidadesArray = array_map('trim', explode(',', $especialidades));
            $query->whereIn('especialidad', $especialidadesArray);
        }

        $especialistas = $query->get(['id', 'especialidad']);

        $this->registrarLog(auth()->id(), 'listar', 'especialistas');

        if ($especialistas->isEmpty()) {
            return response()->json(['message' => 'No hay especialistas disponibles'], 404);
        }

        return response()->json(['especialistas' => $especialistas]);
    }


    /**
     * Mostrar un especialista específico.
     *
     * RUTA:
     *  GET /especialistas/{id}
     *
     * @OA\Get(
     *   path="/especialistas/{id}",
     *   summary="Ver un especialista por ID",
     *   description="Devuelve la información de un especialista concreto, incluyendo datos básicos del usuario asociado.",
     *   tags={"Especialistas"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del especialista",
     *     @OA\Schema(type="integer", example=3)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Especialista encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="especialista",
     *         type="object",
     *         example={
     *           "id": 3,
     *           "user_id": 10,
     *           "especialidad": "Nutrición Clínica",
     *           "user": {
     *             "id": 10,
     *             "nombre": "María",
     *             "apellidos": "Pérez López",
     *             "email": "maria@example.com"
     *           }
     *         }
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Especialista no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Especialista no encontrado")
     *     )
     *   )
     * )
     * 
     * @param int $id ID del especialista que deseamos ver
     * @return JsonResponse devuelve una respuesta JSON con los detalles del especialista o un mensaje de error si no se encuentra.
     */
    public function verEspecialista(int $id): JsonResponse
    {
        $especialista = Especialista::with('user')->find($id);

        if (!$especialista) {
            $this->registrarLog(auth()->id(), 'mostrar_especialista_fallido', 'especialistas', $id);
            return response()->json(['message' => 'Especialista no encontrado'], 404);
        }

        $this->registrarLog(auth()->id(), 'mostrar_especialista', 'especialistas', $id);
        return response()->json(['especialista' => $especialista]);
    }


    /**
     * Listar especialistas por nombre.
     *
     * RUTA:
     *  GET /especialistapornombre
     *
     * @OA\Get(
     *   path="/especialistapornombre",
     *   summary="Listar especialistas por nombre",
     *   description="Devuelve un listado simplificado de especialistas (id de usuario y nombre) para usar en selectores.",
     *   tags={"Especialistas"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de especialistas",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="especialistas",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           example={"id": 12, "nombre": "Ana Martínez"}
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No hay especialistas disponibles",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No hay especialistas disponibles")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener los especialistas",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al obtener los especialistas")
     *     )
     *   )
     * )
     *
     * @return JsonResponse esta función devuelve una respuesta JSON con el listado de especialistas.
     * @throws \Exception Envía un mensaje de error si no se encuentra el paciente.
     */

    public function listarEspecialistasPorNombre(): JsonResponse
    {
        try {
            $especialistas = Especialista::with('user')->get();

            if ($especialistas->isEmpty()) {
                $this->registrarLog(auth()->id(), 'listar_especialistas_por_nombre_no_encontrados');
                return response()->json(['message' => 'No hay especialistas disponibles'], 404);
            }

            $resultado = $especialistas->map(fn($e) => [
                'id' => $e->user->id,
                'nombre' => $e->user->nombre,
            ]);

            $this->registrarLog(auth()->id(), 'listar', 'listado_especialistas_por_nombre');
            return response()->json(['especialistas' => $resultado]);

        } catch (\Throwable $e) {
            $this->logError(auth()->id(), 'Error al obtener especialistas', $e->getMessage());
            return response()->json(['message' => 'Error al obtener los especialistas'], 500);
        }
    }


    /**
     * Actualizar la información de un especialista.
     *
     * RUTA:
     *  PUT /especialistas/{id}
     *
     * BODY:
     *  {
     *    "nombre": "opcional",
     *    "apellidos": "opcional"
     *  }
     *
     * @OA\Put(
     *   path="/especialistas/{id}",
     *   summary="Actualizar especialista",
     *   description="Actualiza los datos básicos de un especialista (nombre y apellidos).",
     *   tags={"Especialistas"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del especialista a actualizar",
     *     @OA\Schema(type="integer", example=3)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="nombre", type="string", nullable=true, example="Laura"),
     *       @OA\Property(property="apellidos", type="string", nullable=true, example="García Romero")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Especialista actualizado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Especialista actualizado correctamente"),
     *       @OA\Property(
     *         property="especialista",
     *         type="object",
     *         example={
     *           "id": 3,
     *           "user_id": 10,
     *           "especialidad": "Nutrición Clínica",
     *           "nombre": "Laura",
     *           "apellidos": "García Romero"
     *         }
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="No se proporcionaron campos para actualizar",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No se proporcionaron campos para actualizar")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Especialista no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Especialista no encontrado")
     *     )
     *   )
     * )
     *
     * @param Request $solicitud parámetro de solicitud que contiene los datos a actualizar
     * @param int $id ID del especialista que se desea actualizar
     * @return JsonResponse devuelve una respuesta JSON con los detalles del especialista actualizado o un mensaje de error si no se encuentra el especialista.
     */
    public function actualizarEspecialista(Request $solicitud, int $id): JsonResponse
    {
        $especialista = Especialista::find($id);

        if (!$especialista) {
            $this->registrarLog(auth()->id(), 'actualizar_especialista_fallido', 'Especialista no encontrado', $id);
            return response()->json(['message' => 'Especialista no encontrado'], 404);
        }

        if (!$solicitud->hasAny(['nombre', 'apellidos'])) {
            return response()->json(['message' => 'No se proporcionaron campos para actualizar'], 400);
        }

        $solicitud->validate([
            'nombre' => 'nullable|string',
            'apellidos' => 'nullable|string',
        ]);

        $especialista->fill($solicitud->only(['nombre', 'apellidos']));
        $especialista->save();

        $this->registrarLog(auth()->id(), 'actualizar_especialista', 'Actualización exitosa', $id);

        return response()->json([
            'message' => 'Especialista actualizado correctamente',
            'especialista' => $especialista,
        ]);
    }



    /**
     * Borrar (dar de baja) un especialista.
     *
     * Esta operación:
     * - Cancela las citas futuras/no cerradas del especialista.
     * - Notifica a los pacientes afectados.
     * - Cambia el rol del usuario asociado a "usuario".
     *
     * RUTA:
     *  DELETE /especialistas/{id}
     *
     * @OA\Delete(
     *   path="/especialistas/{id}",
     *   summary="Dar de baja a un especialista",
     *   description="Da de baja a un especialista, cancela sus citas futuras/no cerradas y notifica a los pacientes.",
     *   tags={"Especialistas"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del especialista a dar de baja",
     *     @OA\Schema(type="integer", example=4)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Especialista dado de baja correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Especialista dado de baja. Citas afectadas canceladas y pacientes notificados."),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         example={
     *           "especialista_id": 4,
     *           "citas_canceladas": 5,
     *           "notificaciones_enviadas": 4,
     *           "rol_nuevo": "usuario"
     *         }
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Especialista no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Especialista no encontrado")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error interno al eliminar especialista",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error interno al eliminar especialista")
     *     )
     *   )
     * )
     *
     * @param int $id ID del especialista que se desea eliminar
     * @return JsonResponse devuelve una respuesta JSON con un mensaje de confirmación o un mensaje de error si no se encuentra el especialista.
     * @throws \Exception si ocurre un error al intentar eliminar el especialista.
     * 
     */


    public function borrarEspecialista(int $id): JsonResponse
    {
        $actorId = auth()->id();
        $codigo = 200;
        $respuesta = [];

        try {
            $especialista = Especialista::with('user')->find($id);

            if (!$especialista) {
                $codigo = 404;
                $respuesta = ['message' => 'Especialista no encontrado'];
                $this->registrarLog($actorId, 'eliminar_especialista_fallido', 'especialistas', $id);
                return response()->json($respuesta, $codigo);
            }

            DB::beginTransaction();

            $userEsp = $especialista->user;
            $nombreEspecialista = trim(($userEsp->nombre ?? '') . ' ' . ($userEsp->apellidos ?? ''));

            $totalNotificadas = 0;
            $totalCitasAfectadas = 0;

            // Citas futuras/no cerradas del especialista
            $queryCitas = Cita::with('paciente.user')
                ->where('id_especialista', $especialista->getKey())
                ->whereNotIn('estado', ['cancelada', 'realizada'])
                ->where('fecha_hora_cita', '>=', now())
                ->orderBy('id_cita');

            // El chunk evita picos de memoria si hay muchas citas
            $queryCitas->chunk(200, function ($citas) use ($nombreEspecialista, &$totalNotificadas, &$totalCitasAfectadas) {
                foreach ($citas as $cita) {
                    $totalCitasAfectadas++;

                    // Notificación al paciente
                    $pacUser = $cita->paciente->user ?? null;
                    if ($pacUser && filter_var($pacUser->email ?? '', FILTER_VALIDATE_EMAIL)) {
                        try {
                            $fechaHora = optional($cita->fecha_hora_cita)->format('d-m-Y H:i');
                            $pacUser->notify(new EspecialistaBajaNotificacion(
                                nombreEspecialista: $nombreEspecialista,
                                fechaHoraCita: $fechaHora
                            ));
                            $totalNotificadas++;
                        } catch (\Throwable $mailEx) {
                            Log::warning('Fallo notificación baja especialista', [
                                'dest_user_id' => $pacUser->id ?? null,
                                'cita_id' => $cita->id_cita ?? null,
                                'error' => $mailEx->getMessage(),
                            ]);
                            // seguimos para que la baja no se rompa por el mail
                        }
                    }

                    // Cancela la cita
                    $cita->estado = 'cancelada';
                    // Se deja un comentario en la cita para dejar un rastro.
                    $cita->comentario = trim(($cita->comentario ?? '') . "\nCancelada por baja del especialista.");
                    $cita->save();
                }
            });

            // Cambiar el rol del usuario del especialista
            $userEsp->syncRoles(['usuario']);

            DB::commit();

            $this->registrarLog($actorId, 'eliminar_especialista', 'especialistas', $especialista->getKey());

            $respuesta = [
                'message' => 'Especialista dado de baja. Citas afectadas canceladas y pacientes notificados.',
                'data' => [
                    'especialista_id' => $especialista->getKey(),
                    'citas_canceladas' => $totalCitasAfectadas,
                    'notificaciones_enviadas' => $totalNotificadas,
                    'rol_nuevo' => 'usuario',
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al eliminar especialista'];
            $this->logError($actorId, 'eliminar_especialista_error', [
                'especialista_id' => $id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }

        return response()->json($respuesta, $codigo);
    }



    /**
     * Crear o restaurar un especialista.
     *
     * - Si el usuario nunca ha sido especialista: crea un registro nuevo.
     * - Si ya lo fue y está en soft delete: lo restaura.
     *
     * RUTA:
     *  POST /especialistas
     *
     * @OA\Post(
     *   path="/especialistas",
     *   summary="Crear o restaurar un especialista",
     *   description="Crea un nuevo especialista asociado a un usuario existente o restaura uno dado de baja (soft delete).",
     *   tags={"Especialistas"},
     *   security={{"sanctum":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"user_id","especialidad"},
     *       @OA\Property(property="user_id", type="integer", example=15, description="ID del usuario que se convertirá en especialista"),
     *       @OA\Property(property="especialidad", type="string", maxLength=50, example="Nutrición Deportiva")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Especialista creado o restaurado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Especialista creado correctamente"),
     *       @OA\Property(property="user", type="object"),
     *       @OA\Property(property="especialista", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Errores de validación",
     *     @OA\JsonContent(
     *       @OA\Property(property="errors", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error interno al crear/restaurar especialista",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error interno al crear/restaurar especialista")
     *     )
     *   )
     * )
     * 
     * @param  \Illuminate\Http\Request  $solicitud request que contiene los datos del especialista
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con un mensaje de éxito o error y el código de respuesta HTTP.
     */
    public function nuevoEspecialista(Request $solicitud): JsonResponse
    {
        $codigo = 201;
        $respuesta = [];
        $user = null;

        //Validación
        $validator = Validator::make($solicitud->all(), [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('especialistas', 'user_id')->whereNull('deleted_at'),
            ],
            'especialidad' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $user = User::findOrFail($solicitud->user_id);

            //Verificar si ya fue especialista anteriormente
            $especialista = Especialista::withTrashed()->where('user_id', $user->id)->first();

            if ($especialista && $especialista->trashed()) {
                $especialista->restore();
                $especialista->especialidad = $solicitud->especialidad;
                $especialista->save();

                $user->syncRoles('especialista');

                $this->registrarLog(auth()->id(), 'restaurar_especialista', "Especialista restaurado, user_id: {$user->id}", $especialista->id);

                $respuesta = [
                    'message' => 'Especialista restaurado correctamente',
                    'user' => $user,
                    'especialista' => $especialista,
                ];
            } else {
                //Crear un nuevo especialista
                $especialista = Especialista::create([
                    'user_id' => $user->id,
                    'especialidad' => $solicitud->especialidad,
                ]);

                $user->assignRole('especialista');

                $this->registrarLog(auth()->id(), 'crear_especialista', "Especialista creado, user_id: {$user->id}", $especialista->id);

                $respuesta = [
                    'message' => 'Especialista creado correctamente',
                    'user' => $user,
                    'especialista' => $especialista,
                ];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError(auth()->id(), 'Error en nuevoEspecialista: ' . $e->getMessage(), $user?->id);
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al crear/restaurar especialista'];
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Lista los especialistas para la vista de administrador,
     * agregando datos personales desde la tabla users.
     *
     * RUTA:
     *  GET /especialistasfull
     *
     * @OA\Get(
     *   path="/especialistasfull",
     *   summary="Listar especialistas (vista administrador)",
     *   description="Devuelve listado completo de especialistas con datos del usuario asociado. Solo para administradores.",
     *   tags={"Especialistas"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de especialistas",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(
     *         type="object",
     *         example={
     *           "id_especialista": 4,
     *           "user_id": 15,
     *           "nombre_apellidos": "Carlos López Pérez",
     *           "email": "carlos@example.com",
     *           "telefono": "600123123",
     *           "especialidad": "Nutrición Deportiva",
     *           "fecha_alta": "2025-05-01"
     *         }
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado.",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No autorizado.")
     *     )
     *   )
     * )
     *
     * @return JsonResponse devuelve un json con la lista de especialistas o un mensaje de error.
     */

    public function listarEspecialistasFull(): JsonResponse
    {
        if (!auth()->check() || !auth()->user()->hasRole('administrador')) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $especialistas = Especialista::with('user')
            ->get()
            ->map(function ($especialista) {
                return [
                    'id_especialista' => $especialista->id,
                    'user_id' => $especialista->user_id,
                    'nombre_apellidos' => $especialista->user->nombre . ' ' . $especialista->user->apellidos,
                    'email' => $especialista->user->email,
                    'telefono' => $especialista->user->telefono,
                    'especialidad' => $especialista->especialidad,
                    'fecha_alta' => $especialista->created_at->format('Y-m-d'),
                ];
            });

        $this->registrarLog(auth()->id(), 'listar_todos_los_especialistas_', 'especialistas');

        return response()->json($especialistas);
    }

    /**
     * Lista todas las especialidades.
     *
     * RUTA:
     *  GET /especialidades
     *
     * @OA\Get(
     *   path="/especialidades",
     *   summary="Listar especialidades",
     *   description="Devuelve el listado de las distintas especialidades registradas en la tabla de especialistas.",
     *   tags={"Especialistas"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de especialidades",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(type="string", example="Nutrición Clínica")
     *     )
     *   )
     * )
     *
     * @return JsonResponse
     */
    public function listarEspecialidades(): JsonResponse
    {
        $especialidades = Especialista::select('especialidad')->distinct()->pluck('especialidad');
        return response()->json($especialidades);
    }

    /**
     * Lista especialistas filtrados por especialidad.
     *
     * RUTA:
     *  GET /especialistas-por-especialidad?especialidad=...
     *
     * @OA\Get(
     *   path="/especialistas-por-especialidad",
     *   summary="Listar especialistas por especialidad",
     *   description="Devuelve especialistas filtrados por una especialidad concreta.",
     *   tags={"Especialistas"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="especialidad",
     *     in="query",
     *     required=true,
     *     description="Nombre de la especialidad a filtrar",
     *     @OA\Schema(type="string", example="Nutrición Clínica")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Listado de especialistas filtrado",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(
     *         type="object",
     *         example={
     *           "id": 3,
     *           "user": {
     *             "nombre": "María",
     *             "apellidos": "Pérez",
     *             "email": "maria@example.com"
     *           }
     *         }
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Falta el parámetro especialidad",
     *     @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="Se requiere el parámetro especialidad")
     *     )
     *   )
     * )
     *
     * @param Request $solicitud
     * @return JsonResponse
     */
    public function listarEspecialistasPorEspecialidad(Request $solicitud): JsonResponse
    {
        $especialidad = $solicitud->query('especialidad');

        if (!$especialidad) {
            return response()->json(['error' => 'Se requiere el parámetro especialidad'], 422);
        }

        $especialistas = Especialista::with('user')
            ->where('especialidad', $especialidad)
            ->get()
            ->map(function ($especialista) {
                return [
                    'id' => $especialista->id,
                    'user' => [
                        'nombre' => $especialista->user->nombre,
                        'apellidos' => $especialista->user->apellidos,
                        'email' => $especialista->user->email,
                    ],
                ];
            });

        return response()->json($especialistas);
    }

    /**
     * Devuelve el perfil del especialista autenticado.
     *
     * Respuesta:
     *  {
     *    "user": { id, nombre, apellidos, email },
     *    "especialista_id": int|null,
     *    "especialidad": string|null
     *  }
     *
     * RUTA:
     *  GET /perfilespecialista
     *
     * @OA\Get(
     *   path="/perfilespecialista",
     *   summary="Perfil del especialista autenticado",
     *   description="Devuelve los datos del usuario especialista autenticado y su información básica.",
     *   tags={"Especialistas"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Perfil del especialista",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="user",
     *         type="object",
     *         example={
     *           "id": 10,
     *           "nombre": "María",
     *           "apellidos": "Pérez López",
     *           "email": "maria@example.com"
     *         }
     *       ),
     *       @OA\Property(property="especialista_id", type="integer", nullable=true, example=3),
     *       @OA\Property(property="especialidad", type="string", nullable=true, example="Nutrición Clínica")
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No autorizado")
     *     )
     *   )
     * )
     *
     * @return JsonResponse
     */
    public function perfilEspecialista(): JsonResponse
    {
        $user = auth()->user();

        if (!$user || !$user->hasRole('especialista')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $especialista = Especialista::select('id', 'user_id')
            ->where('user_id', $user->id)
            ->first();

        // Devolvemos un objeto de usuario, Laravel oculta el password.
        // y agregamos el especialista_id y la especialidad
        return response()->json([
            'user' => $user->only(['id', 'nombre', 'apellidos', 'email']),
            'especialista_id' => $especialista?->id,
            'especialidad' => $especialista?->especialidad,
        ]);
    }
}
