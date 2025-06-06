<?php

namespace App\Http\Controllers;

use App\Models\Especialista;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EspecialistaController extends Controller
{
    use Loggable;

    /**
     * Mostrar todos los especialistas.
     * Devolverá una lista de todos los especialistas registrados en la base de datos.
     * @return \Illuminate\Http\JsonResponse devolverá una respuesta JSON con el listado de especialistas o un mensaje de error si no hay especialistas registrados.
     */
    public function listarEspecialistas(Request $request): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;

        $especialidadesParam = $request->query('especialidades');

        if ($especialidadesParam) {
            $especialidades = array_map('trim', explode(',', $especialidadesParam));
            $especialistas = Especialista::whereIn('especialidad', $especialidades)
                ->get(['id', 'especialidad']);
        } else {
            $especialistas = Especialista::all(['id', 'especialidad']);
        }

        if ($especialistas->isEmpty()) {
            $this->registrarLog(auth()->id(), 'listar', 'especialistas', null);
            $respuesta = ['message' => 'No hay especialistas disponibles'];
            $codigo = 404;
        } else {
            $this->registrarLog(auth()->id(), 'listar', 'especialistas', null);
            $respuesta = ['especialistas' => $especialistas];
        }

        return response()->json($respuesta, $codigo);
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
        $especialista = Especialista::find($id);

        $respuesta = [];
        $codigo = 200;

        if (!$especialista) {
            $this->registrarLog(auth()->id(), 'mostrar_especialista_fallido', 'especialistas',$id );
            $respuesta = ['message' => 'Especialista no encontrado'];
            $codigo = 404;
        } else {
            $this->registrarLog(auth()->id(), 'mostrar_especialista', 'especialistas',$id );
            $respuesta = ['especialista' => $especialista];
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Listar especialistas por nombre
     * Función para listar especialistas por id y nombre.
     * @return \Illuminate\Http\JsonResponse esta función devuelve una respuesta JSON con el listado de especialistas.
     * @throws \Exception Envía un mensaje de error si no se encuentra el paciente.
     */

    public function listarEspecialistasPorNombre(): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;
        
        try{
            $user = Auth::user()->id;
            $especialista = Especialistas::all();
            $usuarios = User::all();

            if($especialista->isEmpty()){
                $this->registrarLog(auth()->id(), 'listar_especialistas_por_nombre_no_encontrados',$user);
                $respuesta = ['message' => 'No hay especialistas disponibles'];
                $codigo = 404;
            }else{
                $this->registrarLog(auth()->id(), 'listar', 'listado_especialistas_por_nombre', $user);
                $respuesta = [
                    'id' => $especialista->usuario->id,
                    'nombre' => $especialista->usuario->nombre,
                ];
            }
        }catch(\Throwable $e){
            $this->logError(auth()->id(),'Error al obtener especialistas: ' . $e->getMessage(), $user);
            $respuesta = ['message' => 'Error al obtener los especialistas'];
            $codigo = 500;
        }
        return response()->json($respuesta, $codigo);
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
        $respuesta = [];
        $codigo = 200;

        $especialista = Especialista::find($id);

        if (!$especialista) {
            $this->registrarLog(auth()->id(), 'actualizar_especialista_fallido', "Especialista ID $id no encontrado", 'especialistas');
            $respuesta = ['message' => 'Especialista no encontrado'];
            $codigo = 404;
        } elseif (!$solicitud->hasAny(['nombre', 'apellidos'])) {
            $respuesta = ['message' => 'No se proporcionaron campos para actualizar'];
            $codigo = 400;
        } else {
            $solicitud->validate([
                'nombre'    => 'string|nullable',
                'apellidos' => 'string|nullable',
            ]);

            $especialista->fill($solicitud->only(['nombre', 'apellidos']));
            $especialista->save();

            $this->registrarLog(auth()->id(), 'actualizar_especialista', "Actualización del especialista ID $id", 'especialistas');

            $respuesta = [
                'message' => 'Especialista actualizado correctamente',
                'especialista' => $especialista,
            ];
        }

        return response()->json($respuesta, $codigo);
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
        $codigo = 200;
        $mensaje = [];

        $especialista = Especialista::find($id);

        if (!$especialista) {
            $this->registrarLog(auth()->id(), 'eliminar_especialista_fallido', 'Especialista no encontrado', 'especialistas');
            $mensaje = ['message' => 'Especialista no encontrado'];
            $codigo = 404;
        } else {
            try {
                $especialista->delete();

                $this->registrarLog(auth()->id(), 'eliminar_especialista', "Especialista ID $id eliminado");

                $mensaje = ['message' => 'Especialista eliminado correctamente'];
            } catch (\Exception $e) {
                $this->registrarLog(auth()->id(), 'eliminar_especialista_error', "Error al eliminar especialista ID $id: " . $e->getMessage());

                $mensaje = ['message' => 'Error interno al eliminar especialista'];
                $codigo = 500;
            }
        }

        return response()->json($mensaje, $codigo);
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
        $respuesta = [];
        $codigo = 201;

        $validar = Validator::make($solicitud->all(), [
            'user_id'      => 'required|integer|exists:users,id',
            'especialidad' => 'required|string|max:150',
        ]);

        if ($validar->fails()) {
            return response()->json(['errors' => $validar->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Buscar el usuario por ID
            $user = User::findOrFail($solicitud->user_id);

            // Crear el especialista
            $especialista = Especialista::create([
                'user_id'      => $user->id,
                'especialidad' => $solicitud->especialidad,
            ]);

            // Asignar el rol al usuario
            $user->assignRole('especialista');

            // Registrar log (si tienes sistema de logging personalizado)
            $this->registrarLog(auth()->id(), 'create', "Especialista creado, user_id: {$user->id}", $especialista->id);


            DB::commit();

            $respuesta = [
                'message' => 'Especialista creado correctamente',
                'user'    => $user,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $respuesta = ['message' => 'Error interno al crear especialista', 'error' => $e->getMessage()];
            $codigo = 500;
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Lista los especialista para la vista de administrador agregando los datos personales desde la tabla users.
     * @return \Illuminate\Http\JsonResponse devuelve un json con la lista de especialistas o un mensaje de error.
     */

    public function listarEspecialistasFull():JsonResponse
    {
        if (!auth()->check() || !auth()->user()->hasRole('administrador')) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $especialistas = Especialista::with('user')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($especialista) {
                return [
                    'id_especialista'=> $especialista->id,
                    'user_id'=> $especialista->user_id,
                    'nombre_apellidos'=> $especialista->user->nombre . ' ' . $especialista->user->apellidos,
                    'email'=> $especialista->user->email,
                    'telefono'=> $especialista->user->telefono,
                    'especialidad'=> $especialista->especialidad,
                    'fecha_alta'=> $especialista->created_at->format('Y-m-d'),
                ];
            });

        $this->registrarLog(auth()->id(), 'listar_todos_los_especialistas_', 'especialistas', null);

        return response()->json($especialistas);
    }

    /**
     * Lista todas las especialidades distintas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listarEspecialidades()
    {
        $especialidades = Especialista::select('especialidad')->distinct()->pluck('especialidad');
        return response()->json($especialidades);
    }

    /**
     * Lista especialistas filtrados por especialidad.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listarEspecialistasPorEspecialidad(Request $request)
    {
        $especialidad = $request->query('especialidad');

        if (!$especialidad) {
            return response()->json(['error' => 'Se requiere el parámetro especialidad'], 422);
        }

        $especialistas = Especialista::with('user')
            ->where('especialidad', $especialidad)
            ->get()
            ->map(function ($especialista) {
                $especialista->usuario = $especialista->user;
                unset($especialista->user);
                return $especialista;
            });

        return response()->json($especialistas);
    }




}
