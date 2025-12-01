<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Especialista;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Traits\Loggable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Notifications\PacienteAltaNotificacion;
use Illuminate\Support\Facades\Notification;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *   name="Usuarios",
 *   description="Gestión de usuarios, roles y datos básicos."
 * )
 */

class UserController extends Controller
{
    use Loggable, Notifiable;

    /**
     * Listar todos los usuarios.
     *
     * RUTA:
     *  GET /usuarios
     *
     * @OA\Get(
     *   path="/usuarios",
     *   summary="Listar todos los usuarios",
     *   description="Obtiene todos los usuarios registrados en el sistema.",
     *   tags={"Usuarios"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de usuarios obtenido correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(type="object")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No hay usuarios registrados",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"No hay usuarios registrados."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener los usuarios",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Ocurrió un error al obtener los usuarios."}}
     *       )
     *     )
     *   )
     * )
     */
    public function listarTodos(): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];

        try {
            $usuarios = User::all();

            if ($usuarios->isEmpty()) {
                $codigo = 404;
                $respuesta = ['errors' => ['general' => ['No hay usuarios registrados.']]];
            } else {
                $respuesta = ['data' => $usuarios];
            }

            $this->registrarLog(auth()->id(), 'listar', 'users');
        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['errors' => ['general' => ['Ocurrió un error al obtener los usuarios.']]];
            $this->logError(auth()->id(), 'Error inesperado al listar todos los usuarios', $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Mostrar un usuario por ID.
     *
     * RUTA:
     *  GET /usuarios/{id}
     *
     * @OA\Get(
     *   path="/usuarios/{id}",
     *   summary="Ver un usuario por ID",
     *   description="Obtiene un usuario de la base de datos según el ID proporcionado.",
     *   tags={"Usuarios"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del usuario",
     *     @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Usuario encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="ID inválido",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"id": {"ID inválido. Debe ser numérico."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Usuario no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Usuario no encontrado."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener el usuario",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Ocurrió un error al obtener el usuario."}}
     *       )
     *     )
     *   )
     * )
     */
    public function verUsuario($id): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];

        if (!is_numeric($id)) {
            $codigo = 400;
            $respuesta = ['errors' => ['id' => ['ID inválido. Debe ser numérico.']]];
            return response()->json($respuesta, $codigo);
        }

        try {
            $usuario = User::find($id);

            if (!$usuario) {
                $codigo = 404;
                $respuesta = ['errors' => ['general' => ['Usuario no encontrado.']]];
            } else {
                $respuesta = ['data' => $usuario];
                $this->registrarLog(auth()->id(), 'ver_usuario', 'users', $id);
            }
        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['errors' => ['general' => ['Ocurrió un error al obtener el usuario.']]];
            $this->logError(auth()->id(), 'Error inesperado al consultar usuario', $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Crear usuario con rol "usuario".
     *
     * RUTA:
     *  POST /usuarios/rol-usuario
     *
     * @OA\Post(
     *   path="/usuarios/rol-usuario",
     *   summary="Crear usuario con rol básico 'usuario'",
     *   description="Registra un usuario con rol 'usuario' sin crear modelo Paciente.",
     *   tags={"Usuarios"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"nombre","apellidos","email","password","password_confirmation","dni_usuario"},
     *       @OA\Property(property="nombre", type="string", example="Ana"),
     *       @OA\Property(property="apellidos", type="string", example="López Martín"),
     *       @OA\Property(property="email", type="string", format="email", example="ana@example.com"),
     *       @OA\Property(property="password", type="string", example="Password123"),
     *       @OA\Property(property="password_confirmation", type="string", example="Password123"),
     *       @OA\Property(property="dni_usuario", type="string", example="12345678A")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Usuario creado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="user", type="object")
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
     *     description="Error inesperado al crear usuario",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Ocurrió un error inesperado al crear el usuario."}}
     *       )
     *     )
     *   )
     * )
     */
    public function crearUsuarioRolUsuario(Request $request): JsonResponse
    {
        return $this->crearUsuarioGenerico($request, 'usuario', false);
    }

    /**
     * Crear usuario con rol "paciente" y modelo Paciente.
     *
     * RUTA:
     *  POST /usuarios
     *
     * @OA\Post(
     *   path="/usuarios",
     *   summary="Crear usuario con rol 'paciente'",
     *   description="Registra un usuario y crea automáticamente el modelo Paciente asociado, asignando rol 'paciente'.",
     *   tags={"Usuarios"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"nombre","apellidos","email","password","password_confirmation","dni_usuario"},
     *       @OA\Property(property="nombre", type="string", example="Laura"),
     *       @OA\Property(property="apellidos", type="string", example="García Pérez"),
     *       @OA\Property(property="email", type="string", format="email", example="laura@example.com"),
     *       @OA\Property(property="password", type="string", example="Password123"),
     *       @OA\Property(property="password_confirmation", type="string", example="Password123"),
     *       @OA\Property(property="dni_usuario", type="string", example="23456789B")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Usuario y paciente creados correctamente",
     *     @OA\JsonContent(
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
     *     description="Error inesperado al crear usuario/paciente",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Ocurrió un error inesperado al crear el usuario."}}
     *       )
     *     )
     *   )
     * )
     */
    public function crearUsuario(Request $request): JsonResponse
    {
        return $this->crearUsuarioGenerico($request, 'paciente', true);
    }


    /**
     * Función privada genérica para crear un nuevo usuario y asignar un rol.
     *
     * @param Request $request datos del usuario nuevo
     * @param string $rolObjetivo
     * @param bool $crearPaciente
     * @return JsonResponse devuelve una respuesta JSON con los datos del usuario creado o un mensaje de error.
     */
    private function crearUsuarioGenerico(Request $request, string $rolObjetivo, bool $crearPaciente): JsonResponse
    {
        $codigo = 201;
        $respuesta = [];

        $validar = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'dni_usuario' => 'required|string|max:9|unique:users,dni_usuario',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'dni_usuario.unique' => 'Este DNI ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validar->fails()) {
            $codigo = 422;
            $respuesta = ['errors' => $validar->errors()];
        } else {
            DB::beginTransaction();
            try {
                $usuario = User::create([
                    'nombre' => $request->input('nombre'),
                    'apellidos' => $request->input('apellidos'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'dni_usuario' => $request->input('dni_usuario'),
                ]);

                //un solo rol a la vez
                $usuario->syncRoles([$rolObjetivo]);

                if ($crearPaciente) {
                    $paciente = Paciente::create([
                        'user_id' => $usuario->id,
                        'numero_historial' => $this->generarNumeroHistorialUnico(),
                        'fecha_alta' => now(),
                    ]);

                    // para que un fallo de mail no rompa la creación
                    try {
                        $especialistaNombre = auth()->user()?->nombre ?? 'uno de nuestros especialistas';
                        Notification::send($usuario, new PacienteAltaNotificacion(
                            nombreEspecialista: $especialistaNombre,
                            numeroHistorial: $paciente->numero_historial,
                        ));
                    } catch (\Throwable $e) {
                        Log::warning('No se pudo enviar notificación de alta de paciente: ' . $e->getMessage());
                    }

                    $respuesta = ['user' => $usuario->load('roles'), 'paciente' => $paciente];
                } else {
                    $respuesta = ['user' => $usuario->load('roles')];
                }

                $UsuarioEnCuestion = auth()->id() ?? $usuario->id;
                $this->registrarLog($UsuarioEnCuestion, 'crear_usuario_' . $rolObjetivo, 'users', $usuario->id);

                DB::commit();
            } catch (QueryException $e) {
                DB::rollBack();
                $UsuarioEnCuestion = auth()->id() ?? $usuario->id;//asigna dependiendo si es un usuario autenticado o el mismo usuario que se está creando
                $codigo = 500;
                $respuesta = [
                    'errors' => [
                        'general' => ['Error en la base de datos. Revisa duplicados o constraints.']
                    ]
                ];
                $this->logError($UsuarioEnCuestion, 'Error DB crear usuario (' . $rolObjetivo . ')', $e->getMessage());
            } catch (\Exception $e) {
                DB::rollBack();
                $UsuarioEnCuestion = auth()->id() ?? ($usuario->id ?? 0);
                $codigo = 500;
                $respuesta = [
                    'errors' => [
                        'general' => ['Ocurrió un error inesperado al crear el usuario.']
                    ]
                ];
                $this->logError($UsuarioEnCuestion, 'Error inesperado crear usuario (' . $rolObjetivo . ')', $e->getMessage());
            }
        }

        return response()->json($respuesta, $codigo);
    }




    /**
     * Actualizar un usuario por ID.
     *
     * RUTA:
     *  PUT /usuarios/{id}
     *
     * @OA\Put(
     *   path="/usuarios/{id}",
     *   summary="Actualizar datos de un usuario",
     *   description="Actualiza los datos básicos de un usuario (nombre, apellidos, email, DNI, etc.).",
     *   tags={"Usuarios"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del usuario a actualizar",
     *     @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"nombre","apellidos","email","dni_usuario"},
     *       @OA\Property(property="nombre", type="string", example="Laura"),
     *       @OA\Property(property="apellidos", type="string", example="García Pérez"),
     *       @OA\Property(property="email", type="string", format="email", example="laura@example.com"),
     *       @OA\Property(property="dni_usuario", type="string", example="12345678A"),
     *       @OA\Property(property="fecha_nacimiento", type="string", format="date", nullable=true, example="1990-01-01"),
     *       @OA\Property(property="telefono", type="string", nullable=true, example="600123123"),
     *       @OA\Property(property="direccion", type="string", nullable=true, example="Calle Falsa 123"),
     *       @OA\Property(property="password", type="string", nullable=true, example="NuevoPassword123"),
     *       @OA\Property(property="password_confirmation", type="string", nullable=true, example="NuevoPassword123")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Usuario actualizado correctamente",
     *     @OA\JsonContent(type="object")
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="ID inválido",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"id": {"El ID proporcionado no es válido."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Usuario no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Usuario no encontrado."}}
     *       )
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
     *     description="Error al actualizar el usuario",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Ocurrió un error inesperado al actualizar el usuario."}}
     *       )
     *     )
     *   )
     * )
     */
    public function actualizarUsuario(Request $solicitud, $id): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];

        if (!is_numeric($id)) {
            $codigo = 400;
            $respuesta = ['errors' => ['id' => ['El ID proporcionado no es válido.']]];
            return response()->json($respuesta, $codigo);
        }

        $usuario = User::find($id);

        if (!$usuario) {
            $codigo = 404;
            $respuesta = ['errors' => ['general' => ['Usuario no encontrado.']]];
            return response()->json($respuesta, $codigo);
        }

        // Reglas de validación
        $reglas = [
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'dni_usuario' => 'required|string|max:9|unique:users,dni_usuario,' . $usuario->id,
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
        ];

        if ($solicitud->filled('password')) {
            $reglas['password'] = 'string|min:6|confirmed';
        }

        $mensajes = [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'dni_usuario.unique' => 'Este DNI ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];

        $validar = Validator::make($solicitud->all(), $reglas, $mensajes);

        if ($validar->fails()) {
            $codigo = 422;
            $respuesta = ['errors' => $validar->errors()];
            return response()->json($respuesta, $codigo);
        }

        try {
            // Asignación masiva segura
            $usuario->fill([
                'nombre' => $solicitud->input('nombre'),
                'apellidos' => $solicitud->input('apellidos'),
                'email' => $solicitud->input('email'),
                'dni_usuario' => $solicitud->input('dni_usuario'),
                'fecha_nacimiento' => $solicitud->input('fecha_nacimiento'),
                'telefono' => $solicitud->input('telefono'),
                'direccion' => $solicitud->input('direccion'),
            ]);

            if ($solicitud->filled('password')) {
                $usuario->password = Hash::make($solicitud->input('password'));
            }

            $usuario->save();

            $this->registrarLog(auth()->id(), 'actualizar_usuario', 'users', $usuario->id);

            $respuesta = User::find($usuario->id);

        } catch (QueryException $e) {
            $codigo = 500;
            $respuesta = [
                'errors' => ['general' => ['Error al actualizar el usuario.']]
            ];
            $this->logError(auth()->id(), 'Error DB al actualizar usuario', $e->getMessage());
        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = [
                'errors' => ['general' => ['Ocurrió un error inesperado al actualizar el usuario.']]
            ];
            $this->logError(auth()->id(), 'Error inesperado al actualizar usuario', $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }



    /**
     * Eliminar un usuario por ID (SoftDelete).
     *
     * RUTA:
     *  DELETE /usuarios/{id}
     *
     * @OA\Delete(
     *   path="/usuarios/{id}",
     *   summary="Eliminar un usuario",
     *   description="Elimina (soft delete) un usuario por su ID.",
     *   tags={"Usuarios"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del usuario a eliminar",
     *     @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Usuario eliminado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="mensaje", type="string", example="Usuario eliminado correctamente.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="ID inválido",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"id": {"El ID proporcionado no es válido."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Usuario no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Usuario no encontrado."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al eliminar el usuario",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Ocurrió un error inesperado al eliminar el usuario."}}
     *       )
     *     )
     *   )
     * )
     */
    public function borrarUsuario($id): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];

        if (!is_numeric($id)) {
            $codigo = 400;
            $respuesta = ['errors' => ['id' => ['El ID proporcionado no es válido.']]];
            return response()->json($respuesta, $codigo);
        }

        $usuario = User::find($id);

        if (!$usuario) {
            $codigo = 404;
            $respuesta = ['errors' => ['general' => ['Usuario no encontrado.']]];
            return response()->json($respuesta, $codigo);
        }

        try {
            $usuario->delete();

            $this->registrarLog(auth()->id(), 'eliminar_usuario', 'users', $id);

            $respuesta = ['mensaje' => 'Usuario eliminado correctamente.'];

        } catch (QueryException $e) {
            $codigo = 500;
            $respuesta = ['errors' => ['general' => ['Error al eliminar el usuario.']]];
            $this->logError(auth()->id(), 'Error DB al eliminar usuario', $e->getMessage());

        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['errors' => ['general' => ['Ocurrió un error inesperado al eliminar el usuario.']]];
            $this->logError(auth()->id(), 'Error inesperado al eliminar usuario', $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }

    //método para cambiar el rol 'especialista' o 'paciente' por el rol 'usuario' recibiendo como parámetro el id de usuario.
    /**
     * Cambiar el rol de un usuario a "usuario" y limpiar vínculos de paciente/especialista.
     *
     * - Si era especialista: elimina citas pendientes por id_especialista y hace soft delete de Especialista.
     * - Si era paciente: elimina citas pendientes por id_paciente y hace soft delete de Paciente.
     *
     * RUTA:
     *  PUT /usuarios/{id}/cambiar-rol
     *
     * @OA\Put(
     *   path="/usuarios/{id}/cambiar-rol",
     *   summary="Cambiar el rol de un usuario a 'usuario'",
     *   description="Cambia el rol de un usuario a 'usuario', elimina sus citas pendientes asociadas como paciente/especialista y hace soft delete de los modelos relacionados.",
     *   tags={"Usuarios"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del usuario cuyo rol se va a cambiar",
     *     @OA\Schema(type="integer", example=10)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Rol cambiado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="mensaje",
     *         type="string",
     *         example="El rol del usuario ha sido cambiado a \"usuario\" y se eliminaron sus citas pendientes."
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="ID inválido",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"id": {"El ID proporcionado no es válido."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Usuario no existe",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"El usuario no existe."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al cambiar el rol del usuario",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Ocurrió un error inesperado al cambiar el rol del usuario."}}
     *       )
     *     )
     *   )
     * )
     */
    public function cambiarRol($id): JsonResponse
    {

        $codigo = 200;
        $respuesta = [];

        if (!is_numeric($id)) {
            $codigo = 422; //datos inválidos
            $respuesta = ['errors' => ['id' => ['El ID proporcionado no es válido.']]];
        } else {
            $usuario = User::find($id);

            if (!$usuario) {
                $codigo = 404;
                $respuesta = ['errors' => ['general' => ['El usuario no existe.']]];
            } else {
                try {
                    DB::transaction(function () use ($usuario) {

                        // Si tiene rol especialista se borran citas pendientes por id de ESPECIALISTA y soft delete
                        if ($usuario->hasRole('especialista')) {
                            $esp = Especialista::where('user_id', $usuario->id)->first();
                            if ($esp) {
                                Cita::where('id_especialista', $esp->id)
                                    ->where('estado', 'pendiente')
                                    ->delete();

                                $esp->delete(); // softDelete
                                \Log::info("El usuario {$usuario->id} dejó de ser especialista. Citas pendientes eliminadas.");
                            }
                        }

                        // Si tiene rol paciente se borran citas pendientes por id de PACIENTE y soft delete
                        if ($usuario->hasRole('paciente')) {
                            $pac = Paciente::where('user_id', $usuario->id)->first();
                            if ($pac) {
                                Cita::where('id_paciente', $pac->id)
                                    ->where('estado', 'pendiente')
                                    ->delete();

                                $pac->delete(); // softDelete
                                \Log::info("El usuario {$usuario->id} dejó de ser paciente. Citas pendientes eliminadas.");
                            }
                        }

                        //Dejarlo solo el rol "usuario"
                        $usuario->syncRoles(['usuario']);

                        $this->registrarLog(auth()->id(), 'actualizar_usuario', 'users', $usuario->id);
                    });

                    $respuesta = [
                        'mensaje' => 'El rol del usuario ha sido cambiado a "usuario" y se eliminaron sus citas pendientes.'
                    ];
                    $codigo = 200;

                } catch (QueryException $e) {
                    $codigo = 500;
                    $respuesta = ['errors' => ['general' => ['Error al cambiar el rol del usuario.']]];
                    $this->logError(auth()->id(), 'Error DB al cambiar rol del usuario', $e->getMessage());

                } catch (\Throwable $e) {
                    $codigo = 500;
                    $respuesta = ['errors' => ['general' => ['Ocurrió un error inesperado al cambiar el rol del usuario.']]];
                    $this->logError(auth()->id(), 'Error inesperado al cambiar rol del usuario', $e->getMessage());
                }
            }
        }
        return response()->json($respuesta, $codigo);
    }


    /**
     * Listar usuarios que solo tienen rol "usuario" (sin paciente/especialista).
     *
     * Sirve para seleccionar usuarios candidatos a ser pacientes o especialistas.
     *
     * RUTA:
     *  GET /usuarios-disponibles
     *
     * @OA\Get(
     *   path="/usuarios-disponibles",
     *   summary="Obtener usuarios sin rol de paciente ni especialista",
     *   description="Devuelve los usuarios cuyo rol es 'usuario' y que no tienen modelos Paciente ni Especialista asociados.",
     *   tags={"Usuarios"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Usuarios obtenidos correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           example={"id": 15, "nombre_apellidos": "Ana López"}
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"autorizacion": {"No autorizado."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al obtener los usuarios",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"general": {"Ocurrió un error al obtener los usuarios."}}
     *       )
     *     )
     *   )
     * )
     */
    public function getUsuariosSinRolEspecialistaNiPaciente(): JsonResponse
    {
        $codigo = 200;
        $respuesta = [];

        if (!auth()->check() || !auth()->user()->hasRole('administrador')) {
            $codigo = 403;
            $respuesta = ['errors' => ['autorizacion' => ['No autorizado.']]];
            return response()->json($respuesta, $codigo);
        }

        try {
            $usuarios = User::role('usuario')
                ->whereDoesntHave('paciente')
                ->whereDoesntHave('especialista')
                ->select('id', 'nombre', 'apellidos')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'nombre_apellidos' => $user->nombre . ' ' . $user->apellidos,
                ]);

            $this->registrarLog(auth()->id(), 'listar_usuarios_para_asignar_especialista', 'users');
            $respuesta = ['data' => $usuarios];
        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['errors' => ['general' => ['Ocurrió un error al obtener los usuarios.']]];
            $this->logError(auth()->id(), 'Error al listar usuarios sin rol paciente/especialista', $e->getMessage());
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Genera un número de historial único con formato XX111111XX.
     * Tiene en cuenta SoftDeletes (withTrashed) y realiza reintentos.
     */
    private function generarNumeroHistorialUnico(int $maxIntentos = 10): string
    {
        for ($i = 0; $i < $maxIntentos; $i++) {
            $numero = $this->generarCandidatoNumeroHistorial();

            // Importante: withTrashed porque usas SoftDeletes en Paciente
            $existe = Paciente::withTrashed()
                ->where('numero_historial', $numero)
                ->exists();

            if (!$existe) {
                return $numero;
            }
        }

        // Último recurso (extremadamente raro): añade una semilla de tiempo y reintenta una vez más
        $numero = $this->generarCandidatoNumeroHistorial();
        if (!Paciente::withTrashed()->where('numero_historial', $numero)->exists()) {
            return $numero;
        }

        throw new \RuntimeException('No se pudo generar un número de historial único tras varios intentos.');
    }

    /**
     * Devuelve un candidato con formato XX111111XX (todo en mayúsculas).
     */
    private function generarCandidatoNumeroHistorial(): string
    {
        $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $l1 = $letras[random_int(0, 25)];
        $l2 = $letras[random_int(0, 25)];
        $num = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $l3 = $letras[random_int(0, 25)];
        $l4 = $letras[random_int(0, 25)];

        return $l1 . $l2 . $num . $l3 . $l4;
    }

}
