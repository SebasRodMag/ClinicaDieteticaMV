<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\Loggable;
use App\Models\Cita;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Services\BajasServices;
use Illuminate\Validation\Rule;
use App\Notifications\PacienteAltaNotificacion;
use Illuminate\Support\Facades\Notification;
use OpenApi\Annotations as OA;

class PacienteController extends Controller
{
    use Loggable;

    public function __construct(private BajasServices $bajasServices)
    {
    }

    /**
     * Muestra una lista de pacientes.
     *
     * Devuelve todos los pacientes registrados con información básica del usuario asociado.
     *
     * RUTA:
     *  GET /pacientes
     * ROLES:
     *  paciente | especialista | administrador
     *
     * @OA\Get(
     *   path="/pacientes",
     *   summary="Listar pacientes",
     *   description="Devuelve una lista de pacientes con datos básicos del usuario asociado (nombre, apellidos, email).",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de pacientes",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="pacientes",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           example={
     *             "id": 5,
     *             "user_id": 10,
     *             "numero_historial": "AB123456CD",
     *             "fecha_alta": "2025-05-01",
     *             "fecha_baja": null,
     *             "created_at": "2025-05-01T10:00:00Z",
     *             "updated_at": "2025-05-01T10:00:00Z",
     *             "deleted_at": null,
     *             "user": {
     *               "id": 10,
     *               "nombre": "Laura",
     *               "apellidos": "García Pérez",
     *               "email": "laura@example.com"
     *             }
     *           }
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No hay pacientes disponibles",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No hay pacientes disponibles")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener pacientes",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al obtener pacientes")
     *     )
     *   )
     * )
     *
     * @return JsonResponse esta función devuelve una respuesta JSON con el listado de pacientes.
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
     * Lista de pacientes con id y nombre completo.
     *
     * RUTA:
     *  GET /pacientespornombre
     *
     * @OA\Get(
     *   path="/pacientespornombre",
     *   summary="Listar pacientes por nombre",
     *   description="Devuelve una lista simplificada de pacientes (id de usuario y nombre completo) para usar en selects.",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de pacientes",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="pacientes",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           example={"id": 10, "nombre": "Laura García Pérez"}
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No hay pacientes disponibles",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No hay pacientes disponibles")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener los pacientes",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al obtener los pacientes")
     *     )
     *   )
     * )
     *
     * @return JsonResponse esta función devuelve una respuesta JSON con el listado de pacientes.
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
     * Crea un nuevo paciente a partir de uno registrado, se agrego esta funcionalidad en nuevoPaciente() y este ha sido deprecado.
     * Registra un nuevo paciente en la base de datos.
     * Se valida que el usuario asociado exista y no esté ya registrado como paciente.
     * 
     * @param \Illuminate\Http\Request $solicitud recibe los datos del paciente
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el paciente creado o un mensaje de error. 
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
     * Mostrar un paciente específico.
     *
     * RUTAS:
     *  GET /pacientes/{id}       (paciente, para ver su propio perfil)
     *  GET /pacientes/{id}/ver   (administrador, vista de detalle)
     *
     * @OA\Get(
     *   path="/pacientes/{id}",
     *   summary="Ver un paciente por ID",
     *   description="Devuelve los datos básicos del paciente indicado.",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del paciente",
     *     @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Paciente encontrado",
     *     @OA\JsonContent(
     *       oneOf={
     *         @OA\Schema(
     *           type="object",
     *           example={
     *             "id": 5,
     *             "user_id": 10,
     *             "numero_historial": "AB123456CD",
     *             "fecha_alta": "2025-05-01",
     *             "fecha_baja": null
     *           }
     *         )
     *       }
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="ID inválido",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="ID inválido")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Paciente no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Paciente no encontrado")
     *     )
     *   )
     * )
     *
     * @OA\Get(
     *   path="/pacientes/{id}/ver",
     *   summary="Ver detalle de paciente (admin)",
     *   description="Devuelve los datos del paciente indicado. Utilizado en la vista de administración.",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del paciente",
     *     @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Paciente encontrado",
     *     @OA\JsonContent(type="object")
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="ID inválido",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="ID inválido")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Paciente no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Paciente no encontrado")
     *     )
     *   )
     * )
     *
     * @param mixed $id
     * @return JsonResponse devuelve una respuesta JSON con los datos del paciente o un mensaje de error.
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
     * Dar de baja (eliminar) un paciente.
     *
     * Cambia el rol del usuario a 'usuario', elimina sus citas y borra el registro de paciente.
     *
     * RUTA:
     *  DELETE /pacientes/{id}
     *
     * @OA\Delete(
     *   path="/pacientes/{id}",
     *   summary="Eliminar (dar de baja) a un paciente",
     *   description="Da de baja a un paciente: elimina sus citas, cambia el rol del usuario asociado a 'usuario' y elimina el registro de paciente.",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del paciente a dar de baja",
     *     @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Paciente dado de baja correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Paciente dado de baja y citas eliminadas correctamente"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         example={
     *           "paciente_id": 5,
     *           "citas_eliminadas": 3,
     *           "rol_usuario": "usuario"
     *         }
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="ID inválido",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="ID inválido")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Paciente no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Paciente no encontrado")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="No se pudo completar la baja del paciente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No se pudo completar la baja del paciente")
     *     )
     *   )
     * )
     *
     * @param mixed $id
     * @return JsonResponse devuelve una respuesta JSON con un mensaje de éxito o un mensaje de error.
     * 
     */
    public function borrarPaciente($id): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;
        $respuesta = ['message' => 'Paciente dado de baja y citas eliminadas correctamente'];

        try {
            // Validación básica de ID
            if (!is_numeric($id)) {
                $codigo = 400;
                $respuesta = ['message' => 'ID inválido'];
                $this->registrarLog($userId, 'borrar_paciente_id_invalido', 'paciente', null);
                return response()->json($respuesta, $codigo);
            }

            $paciente = Paciente::find($id);

            if (!$paciente) {
                $codigo = 404;
                $respuesta = ['message' => 'Paciente no encontrado'];
                $this->registrarLog($userId, 'borrar_paciente_id_no_encontrado', 'paciente', $id);
                return response()->json($respuesta, $codigo);
            }

            DB::beginTransaction();

            // 1) Cambiar rol del usuario asociado a 'usuario'
            $user = $paciente->user ?? $paciente->usuario;
            if ($user) {
                if (property_exists($user, 'rol') || array_key_exists('rol', $user->getAttributes())) {
                    $user->rol = 'usuario';
                    $user->save();
                } elseif (method_exists($user, 'syncRoles')) {
                    $user->syncRoles(['usuario']); // Spatie
                }
            }

            // 2) Eliminar citas del paciente (primero citas para evitar FK)
            $citasIds = Cita::where(function ($q) use ($id) {
                $q->where('paciente_id', $id)->orWhere('id_paciente', $id);
            })
                ->pluck('id');

            $totalCitas = $citasIds->count();
            if ($totalCitas > 0) {
                Cita::whereIn('id', $citasIds)->delete();
            }

            // 3) Borrar fila del paciente (si usas SoftDeletes y quieres borrado físico, usa forceDelete)
            if (method_exists($paciente, 'forceDelete')) {
                $paciente->forceDelete();
            } else {
                $paciente->delete();
            }

            DB::commit();

            // Log éxito + datos útiles
            $this->registrarLog($userId, 'borrar_paciente', 'paciente', $id);

            // Devolver detalle opcional
            $respuesta['data'] = [
                'paciente_id' => (int) $id,
                'citas_eliminadas' => (int) $totalCitas,
                'rol_usuario' => $user ? ($user->rol ?? 'usuario') : null,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $codigo = 500;
            $respuesta = ['message' => 'No se pudo completar la baja del paciente'];
            // Log del error técnico
            if (method_exists($this, 'logError')) {
                $this->logError($userId, 'borrar_paciente_error', $e->getMessage());
            } else {
                // fallback al registrarLog si no tienes logError
                $this->registrarLog($userId, 'borrar_paciente_error', 'paciente', $id);
            }
            report($e);
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Devuelve información completa de todos los pacientes,
     * incluyendo la última cita (estado y especialista asociado).
     *
     * RUTA:
     *  GET /pacientestodos
     * ROLES:
     *  administrador
     *
     * @OA\Get(
     *   path="/pacientestodos",
     *   summary="Listado completo de pacientes (admin)",
     *   description="Devuelve un listado de pacientes con datos de contacto, número de historial, estado y última cita (incluyendo especialista).",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de pacientes",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(
     *         type="object",
     *         example={
     *           "id": 10,
     *           "nombre": "Laura",
     *           "apellidos": "García Pérez",
     *           "telefono": "600123123",
     *           "email": "laura@example.com",
     *           "numero_historial": "AB123456CD",
     *           "fecha_alta": "2025-05-01",
     *           "fecha_baja": null,
     *           "estado": "pendiente",
     *           "especialista_asociado": 3,
     *           "especialista": {
     *             "id_especialista": 3,
     *             "usuario": {
     *               "id_usuario": 7,
     *               "nombre": "María",
     *               "apellidos": "Pérez",
     *               "email": "maria@example.com",
     *               "telefono": "600111222"
     *             }
     *           }
     *         }
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No se encontraron pacientes",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No se encontraron pacientes")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener los pacientes",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al obtener los pacientes")
     *     )
     *   )
     * )
     *
     * @return JsonResponse devuelve una respuesta JSON con la información de los pacientes.
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
     * Lista la relación de pacientes con su última cita y especialista.
     *
     * RUTA:
     *  GET /pacienteslistado
     * ROLES:
     *  administrador
     *
     * @OA\Get(
     *   path="/pacienteslistado",
     *   summary="Pacientes con información de especialista y última cita",
     *   description="Devuelve para cada paciente sus datos, la última cita y el especialista asociado.",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de pacientes con especialista",
     *     @OA\JsonContent(type="array", @OA\Items(type="object"))
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error interno al obtener pacientes con especialista",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error interno al obtener pacientes con especialista")
     *     )
     *   )
     * )
     *
     * @return JsonResponse
     */
    public function pacientesConEspecialista(): JsonResponse
    {
        $userId = auth()->id();
        $codigo = 200;

        try {
            $pacientes = Paciente::with(['user', 'citas.especialista.user'])->get();

            $resultado = $pacientes->map(function ($paciente) {
                $ultimaCita = $paciente->citas
                    ->sortByDesc('fecha_hora_cita')
                    ->first();

                $especialista = $ultimaCita?->especialista;
                $usuarioEspecialista = $especialista?->user;

                return [
                    'id' => $paciente->id,
                    'user_id' => $paciente->user_id,
                    'numero_historial' => $paciente->numero_historial,
                    'fecha_alta' => optional($paciente->fecha_alta)->format('Y-m-d'),
                    'fecha_baja' => optional($paciente->fecha_baja)->format('Y-m-d'),
                    'created_at' => optional($paciente->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => optional($paciente->updated_at)->format('Y-m-d H:i:s'),
                    'deleted_at' => optional($paciente->deleted_at)->format('Y-m-d H:i:s'),

                    'ultima_cita' => $ultimaCita ? [
                        'id_cita' => $ultimaCita->id_cita,
                        'id_paciente' => $ultimaCita->id_paciente,
                        'id_especialista' => $ultimaCita->id_especialista,
                        'fecha_hora_cita' => optional($ultimaCita->fecha_hora_cita)->format('Y-m-d H:i:s'),
                        'tipo_cita' => $ultimaCita->tipo_cita,
                        'estado' => $ultimaCita->estado,
                        'es_primera' => $ultimaCita->es_primera,
                        'comentario' => $ultimaCita->comentario,
                        'created_at' => optional($ultimaCita->created_at)->format('Y-m-d H:i:s'),
                        'updated_at' => optional($ultimaCita->updated_at)->format('Y-m-d H:i:s'),
                        'deleted_at' => optional($ultimaCita->deleted_at)->format('Y-m-d H:i:s'),

                        'especialista' => $especialista ? [
                            'id' => $especialista->id,
                            'user_id' => $especialista->user_id,
                            'especialidad' => $especialista->especialidad,
                            'created_at' => optional($especialista->created_at)->format('Y-m-d H:i:s'),
                            'updated_at' => optional($especialista->updated_at)->format('Y-m-d H:i:s'),
                            'deleted_at' => optional($especialista->deleted_at)->format('Y-m-d H:i:s'),

                            'usuario' => $usuarioEspecialista ? [
                                'id' => $usuarioEspecialista->id,
                                'nombre' => $usuarioEspecialista->nombre,
                                'apellidos' => $usuarioEspecialista->apellidos,
                                'dni_usuario' => $usuarioEspecialista->dni_usuario,
                                'email' => $usuarioEspecialista->email,
                                'email_verified_at' => optional($usuarioEspecialista->email_verified_at)->format('Y-m-d H:i:s'),
                                'direccion' => $usuarioEspecialista->direccion,
                                'fecha_nacimiento' => optional($usuarioEspecialista->fecha_nacimiento)->format('Y-m-d'),
                                'telefono' => $usuarioEspecialista->telefono,
                                'created_at' => optional($usuarioEspecialista->created_at)->format('Y-m-d H:i:s'),
                                'updated_at' => optional($usuarioEspecialista->updated_at)->format('Y-m-d H:i:s'),
                                'deleted_at' => optional($usuarioEspecialista->deleted_at)->format('Y-m-d H:i:s'),
                            ] : null,
                        ] : null,
                    ] : null,

                    'usuario' => $paciente->user ? [
                        'id' => $paciente->user->id,
                        'nombre' => $paciente->user->nombre,
                        'apellidos' => $paciente->user->apellidos,
                        'dni_usuario' => $paciente->user->dni_usuario,
                        'email' => $paciente->user->email,
                        'email_verified_at' => optional($paciente->user->email_verified_at)->format('Y-m-d H:i:s'),
                        'direccion' => $paciente->user->direccion,
                        'fecha_nacimiento' => optional($paciente->user->fecha_nacimiento)->format('Y-m-d'),
                        'telefono' => $paciente->user->telefono,
                        'created_at' => optional($paciente->user->created_at)->format('Y-m-d H:i:s'),
                        'updated_at' => optional($paciente->user->updated_at)->format('Y-m-d H:i:s'),
                        'deleted_at' => optional($paciente->user->deleted_at)->format('Y-m-d H:i:s'),
                    ] : null,
                ];
            });

            $this->registrarLog($userId, 'listar_pacientes_con_especialista', 'paciente', null);
            return response()->json($resultado, $codigo);

        } catch (\Throwable $e) {
            $this->logError($userId, 'Error al obtener pacientes con especialista: ' . $e->getMessage(), null);
            return response()->json(['message' => 'Error interno al obtener pacientes con especialista'], 500);
        }
    }




    /**
     * Actualiza datos del paciente autenticado, verificando la contraseña actual.
     *
     * RUTA:
     *  PUT /pacientes/{id}
     * ROLES:
     *  paciente (sobre sí mismo)
     *
     * @OA\Put(
     *   path="/pacientes/{id}",
     *   summary="Actualizar datos del paciente (self-service)",
     *   description="Permite al paciente autenticado actualizar sus datos personales, confirmando la contraseña actual.",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del paciente (debe coincidir con el usuario autenticado)",
     *     @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"nombre","apellidos","dni_usuario","email","password_actual"},
     *       @OA\Property(property="nombre", type="string", example="Laura"),
     *       @OA\Property(property="apellidos", type="string", example="García Pérez"),
     *       @OA\Property(property="dni_usuario", type="string", example="12345678A"),
     *       @OA\Property(property="email", type="string", format="email", example="laura@example.com"),
     *       @OA\Property(property="direccion", type="string", nullable=true, example="Calle Falsa 123"),
     *       @OA\Property(property="fecha_nacimiento", type="string", format="date", nullable=true, example="1990-01-01"),
     *       @OA\Property(property="telefono", type="string", nullable=true, example="600123123"),
     *       @OA\Property(property="password_actual", type="string", example="PasswordActual123")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Datos actualizados correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="mensaje", type="string", example="Datos actualizados correctamente"),
     *       @OA\Property(property="paciente", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado",
     *     @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="No autorizado")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Errores de validación o contraseña incorrecta",
     *     @OA\JsonContent(
     *       oneOf={
     *         @OA\Schema(
     *           type="object",
     *           example={"errors": {"email": {"El email ya está en uso"}}}
     *         ),
     *         @OA\Schema(
     *           type="object",
     *           example={"error": "Contraseña actual incorrecta"}
     *         )
     *       }
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Paciente no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="Paciente no encontrado")
     *     )
     *   )
     * )
     *
     * @param Request $request
     * @param int $idPaciente
     * @return JsonResponse
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
     * Cambia la contraseña del paciente autenticado.
     *
     * RUTA:
     *  PUT /pacientes/{id}/cambiar-password
     *
     * @OA\Put(
     *   path="/pacientes/{id}/cambiar-password",
     *   summary="Cambiar contraseña del paciente",
     *   description="Permite al paciente autenticado cambiar su contraseña, confirmando la contraseña actual.",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del paciente (debe coincidir con el usuario autenticado)",
     *     @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"password_actual","password_nuevo"},
     *       @OA\Property(property="password_actual", type="string", example="PasswordActual123"),
     *       @OA\Property(property="password_nuevo", type="string", example="NuevoPassword123"),
     *       @OA\Property(property="password_nuevo_confirmation", type="string", example="NuevoPassword123")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Contraseña actualizada correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="mensaje", type="string", example="Contraseña actualizada correctamente")
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado",
     *     @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="No autorizado")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Errores de validación o contraseña incorrecta",
     *     @OA\JsonContent(
     *       oneOf={
     *         @OA\Schema(
     *           type="object",
     *           example={"errors": {"password_nuevo": {"La contraseña debe tener al menos 8 caracteres"}}}
     *         ),
     *         @OA\Schema(
     *           type="object",
     *           example={"error": "Contraseña actual incorrecta"}
     *         )
     *       }
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Paciente no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="error", type="string", example="Paciente no encontrado")
     *     )
     *   )
     * )
     *
     * @param Request $request
     * @param int $idPaciente
     * @return JsonResponse
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
     * obtener datos de paciente a partir del id de usuario. Deprecado
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
     * Crear o restaurar un paciente.
     *
     * - Si el usuario nunca ha sido paciente: crea un registro nuevo con número de historial único.
     * - Si ya lo fue y está en soft delete: lo restaura y actualiza fecha de alta.
     *
     * RUTA:
     *  POST /nuevo-paciente
     *
     * BODY:
     *  {
     *    "user_id": 15
     *  }
     *
     * @OA\Post(
     *   path="/nuevo-paciente",
     *   summary="Crear o restaurar un paciente",
     *   description="Convierte un usuario en paciente o restaura un paciente dado de baja. Asigna rol 'paciente' y genera número de historial.",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"user_id"},
     *       @OA\Property(property="user_id", type="integer", example=15, description="ID del usuario a convertir / restaurar como paciente")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Paciente creado o restaurado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Paciente creado correctamente"),
     *       @OA\Property(property="user", type="object"),
     *       @OA\Property(property="paciente", type="object")
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
     *     description="Error interno al crear/restaurar paciente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error interno al crear/restaurar paciente")
     *     )
     *   )
     * )
     *
     * @param  Request  $solicitud contiene los datos del paciente
     * @return JsonResponse devuelve una respuesta JSON con un mensaje de éxito o error y el código de respuesta HTTP.
     */
    public function nuevoPaciente(Request $solicitud): JsonResponse
    {
        $codigo = 201;
        $respuesta = [];
        $user = null;

        $validar = Validator::make($solicitud->all(), [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('pacientes', 'user_id')->whereNull('deleted_at'),
            ],
        ]);

        if ($validar->fails()) {
            return response()->json(['errors' => $validar->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::findOrFail($solicitud->user_id);
            $paciente = Paciente::withTrashed()->where('user_id', $user->id)->first();

            if ($paciente && $paciente->trashed()) {
                // posibilidad de restaurar paciente
                $paciente->restore();
                $paciente->fecha_alta = now()->toDateString();
                $paciente->save();

                $user->syncRoles(['paciente']);
                $this->registrarLog(auth()->id(), 'restaurar_paciente', 'pacientes', $paciente->id);

                $respuesta = [
                    'message' => 'Paciente restaurado correctamente',
                    'user' => $user,
                    'paciente' => $paciente,
                ];
            } else {
                //alta nueva genera un número de historial único
                $numeroHistorial = $this->generarNumeroHistorialUnico();

                $paciente = Paciente::create([
                    'user_id' => $user->id,
                    'numero_historial' => $numeroHistorial,
                    'fecha_alta' => now()->toDateString(),
                ]);

                $user->syncRoles(['paciente']);

                // Notificación de alta irá a la cola 'mail'
                $especialistaNombre = auth()->user()?->nombre ?? 'uno de nuestros especialistas';//Por si no se encuentra el nombre del especialista aunque no debería darse el caso
                try {
                    $user->notify(new PacienteAltaNotificacion(
                        nombreEspecialista: $especialistaNombre,
                        numeroHistorial: $paciente->numero_historial
                    ));
                } catch (\Throwable $e) {
                    \Log::warning('Fallo enviando PacienteAltaNotificacion', [
                        'user_id' => $user->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    // Si se produce un error al enviar la notificación, no se interrumpe el proceso de creación del paciente.
                }

                $this->registrarLog(auth()->id(), 'crear_paciente', 'pacientes', $paciente->id);

                $respuesta = [
                    'message' => 'Paciente creado correctamente',
                    'user' => $user,
                    'paciente' => $paciente,
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logError(auth()->id(), 'Error en nuevoPaciente: ' . $e->getMessage(), $user?->id);
            return response()->json(['message' => 'Error interno al crear/restaurar paciente'], 500);
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Obtener los pacientes activos del especialista autenticado.
     *
     * RUTA:
     *  GET /paciente-por-especialista
     * ROLES:
     *  especialista
     *
     * @OA\Get(
     *   path="/paciente-por-especialista",
     *   summary="Pacientes del especialista autenticado",
     *   description="Devuelve los pacientes activos que han tenido citas con el especialista autenticado.",
     *   tags={"Pacientes"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Pacientes obtenidos correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Pacientes obtenidos correctamente"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           example={
     *             "id": 5,
     *             "user_id": 10,
     *             "numero_historial": "AB123456CD",
     *             "nombre": "Laura",
     *             "apellidos": "García Pérez"
     *           }
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado como especialista",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No autorizado como especialista")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener los pacientes del especialista",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al obtener los pacientes del especialista.")
     *     )
     *   )
     * )
     *
     * @return JsonResponse
     */
    public function listarPacientesDelEspecialista(): JsonResponse
    {
        $userId = Auth::id();
        $codigo = 200;
        $respuesta = [];

        try {
            $especialista = Auth::user()->especialista ?? null;

            if (!$especialista) {
                $codigo = 403;
                $respuesta = ['message' => 'No autorizado como especialista'];
            } else {
                $pacientes = Paciente::where(function ($query) {
                    $query->whereNull('fecha_baja')
                        ->orWhereColumn('fecha_baja', '<', 'updated_at');
                })
                    ->whereHas('citas', function ($query) use ($especialista) {
                        $query->where('id_especialista', $especialista->id);
                    })
                    ->with('user')
                    ->get();

                // Transformar para enviar solo los datos necesarios
                $datosFiltrados = $pacientes->map(function ($paciente) {
                    return [
                        'id' => $paciente->id,
                        'user_id' => $paciente->user_id,
                        'numero_historial' => $paciente->numero_historial,
                        'nombre' => $paciente->user->nombre ?? '',
                        'apellidos' => $paciente->user->apellidos ?? '',
                    ];
                });

                $respuesta = [
                    'message' => 'Pacientes obtenidos correctamente',
                    'data' => $datosFiltrados
                ];
            }

            $this->registrarLog($userId, 'listar_pacientes_especialista', 'pacientes', null);
        } catch (\Throwable $e) {
            $codigo = 500;
            $respuesta = ['message' => 'Error al obtener los pacientes del especialista.'];
            $this->logError($userId, 'Error al listar pacientes del especialista: ' . $e->getMessage(), $e->getTrace());
        }

        return response()->json($respuesta, $codigo);
    }

    private function generarNumeroHistorialUnico(): string
    {
        do {
            $prefijo = strtoupper(Str::random(2));
            $numeros = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $sufijo = strtoupper(Str::random(2));

            $numeroHistorial = $prefijo . $numeros . $sufijo;
        } while (Paciente::where('numero_historial', $numeroHistorial)->exists());

        return $numeroHistorial;
    }
}
