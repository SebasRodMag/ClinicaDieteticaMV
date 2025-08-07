<?php

namespace App\Http\Controllers;

use App\Models\Especialista;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Cita;
use App\Notifications\EspecialistaBajaNotificacion;
use Illuminate\Support\Facades\Notification;

class EspecialistaController extends Controller
{
    use Loggable;

    /**
     * Mostrar todos los especialistas.
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
     * Esta función busca un especialista por su ID y devuelve sus detalles.
     * Si el especialista no se encuentra, se devuelve un mensaje de error.
     * Se valida que el ID sea numérico y se maneja el caso en que no se encuentra el especialista.
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
     * Listar especialistas por nombre
     * Función para listar especialistas por id y nombre.
     * @return \Illuminate\Http\JsonResponse esta función devuelve una respuesta JSON con el listado de especialistas.
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
     * Esta función permite actualizar los datos de un especialista existente en la base de datos.
     * Se valida que el ID del especialista exista y se aplican las reglas de validación a los datos de la solicitud.
     *
     * @param Request $solicitud parámetro de solicitud que contiene los datos a actualizar
     * @param int $id ID del especialista que se desea actualizar
     * @throws \Illuminate\Validation\ValidationException si los datos no cumplen con las reglas de validación.
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
     * Borrar un especialista (softDelete).
     * Esta función elimina un especialista de la base de datos de forma segura.
     * Se valida que el ID del especialista exista antes de intentar eliminarlo.
     * Si el especialista no se encuentra, se devuelve un mensaje de error.
     *
     * @param int $id ID del especialista que se desea eliminar
     * @return JsonResponse devuelve una respuesta JSON con un mensaje de confirmación o un mensaje de error si no se encuentra el especialista.
     * @throws \Exception si ocurre un error al intentar eliminar el especialista.
     * 
     */


    public function borrarEspecialista(int $id): JsonResponse
    {
        $userId = auth()->id();
        $codigo = 200;
        $respuesta = [];

        try {
            $especialista = Especialista::with('user')->find($id);

            if (!$especialista) {
                $codigo = 404;
                $respuesta = ['message' => 'Especialista no encontrado'];
                $this->registrarLog($userId, 'eliminar_especialista_fallido', 'Especialista no encontrado', $id);
                return response()->json($respuesta, $codigo);
            }

            DB::beginTransaction();

            $user = $especialista->user;
            $nombreEspecialista = $user?->nombre . ' ' . $user?->apellidos;

            // Obtener citas con sus pacientes
            $citas = Cita::with('paciente.user')
                ->where('id_especialista', $id)
                ->get();

            // Notificar a cada paciente
            foreach ($citas as $cita) {
                $pacienteUser = $cita->paciente->user ?? null;

                if ($pacienteUser && filter_var($pacienteUser->email, FILTER_VALIDATE_EMAIL)) {
                    $fechaHora = optional($cita->fecha_hora_cita)->format('d-m-Y H:i');
                    $pacienteUser->notify(new EspecialistaBajaNotificacion($nombreEspecialista, $fechaHora));
                }
            }

            // Eliminar citas
            $citasIds = $citas->pluck('id_cita');
            Cita::whereIn('id_cita', $citasIds)->delete();

            // Cambiar el rol del especialista
            if ($user && method_exists($user, 'syncRoles')) {
                $user->syncRoles(['usuario']);
            }

            // Eliminar registro del especialista
            method_exists($especialista, 'forceDelete')
                ? $especialista->forceDelete()
                : $especialista->delete();

            DB::commit();

            $respuesta = [
                'message' => 'Especialista dado de baja, citas eliminadas y pacientes notificados',
                'data' => [
                    'especialista_id' => $id,
                    'citas_eliminadas' => $citas->count(),
                    'rol_nuevo' => 'usuario',
                ],
            ];

            $this->registrarLog($userId, 'eliminar_especialista', 'Especialista eliminado y pacientes notificados', $id);
        } catch (\Throwable $e) {
            DB::rollBack();
            $codigo = 500;
            $respuesta = ['message' => 'Error interno al eliminar especialista'];
            $this->logError($userId, 'eliminar_especialista_error', $e->getMessage());
            report($e);
        }

        return response()->json($respuesta, $codigo);
    }



    /**
     * Almacena un nuevo especialista en la base de datos.
     * Esta función recibe una solicitud con los datos del especialista,
     * valida los datos y crea un nuevo registro en la base de datos.
     * Se maneja la transacción para asegurar que los datos se guarden correctamente
     * y se registran los logs correspondientes.
     *
     * @param  \Illuminate\Http\Request  $solicitud request que contiene los datos del especialista
     * @throws \Illuminate\Validation\ValidationException devuelve una excepción si los datos no cumplen con las reglas de validación.
     * @throws \Exception lanza una excepción si ocurre un error al guardar el especialista.
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
     * Lista los especialista para la vista de administrador agregando los datos personales desde la tabla users.
     * @return \Illuminate\Http\JsonResponse devuelve un json con la lista de especialistas o un mensaje de error.
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
     * Lista todas las especialidades distintas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listarEspecialidades(): JsonResponse
    {
        $especialidades = Especialista::select('especialidad')->distinct()->pluck('especialidad');
        return response()->json($especialidades);
    }

    /**
     * Lista especialistas filtrados por especialidad.
     *
     * @param Request $solicitud
     * @return \Illuminate\Http\JsonResponse
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
}
