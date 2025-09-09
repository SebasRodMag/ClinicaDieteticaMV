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
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Notifications\PacienteAltaNotificacion;
use Illuminate\Support\Facades\Notification;


class UserController extends Controller
{
    use Loggable;

    /**
     * 
     * Función para listar todos los usuarios.
     * Obtiene todos los usuarios de la base de datos y devuelve una respuesta JSON.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con el listado de usuarios.
     * 
     * 
     *  */
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
     * 
     * Función para mostrar un usuario por ID.
     * Obtiene un usuario de la base de datos según el ID proporcionado y devuelve una respuesta JSON
     * @param int $id id del usuario a buscar
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con los datos del usuario o un mensaje de error.
     * 
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
     * Crea un usuario con rol "usuario".
     */
    public function crearUsuarioRolUsuario(Request $request): JsonResponse
    {
        return $this->crearUsuarioGenerico($request, 'usuario', false);
    }

    /**
     * Crea un usuario con rol "paciente" y su modelo Paciente.
     */
    public function crearUsuario(Request $request): JsonResponse
    {
        return $this->crearUsuarioGenerico($request, 'paciente', true);
    }


    /**
     * 
     * Función privada genérica para para crear un nuevo usuario y asignar un rol.
     * Valida los datos de entrada y crea un nuevo usuario en la base de datos.
     * 
     * @param Request $solicitud datos del usuario nuevo
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con los datos del usuario creado o un mensaje de error.
     * @throws \Illuminate\Validation\ValidationException si la validación falla.
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
     * 
     * Función para actualizar un usuario por ID.
     * * Valida los datos de entrada y actualiza el usuario en la base de datos.
     * 
     * @param Request $solicitud datos del usuario a actualizar
     * @param int $id ID del usuario que se va a actualizar
     * @return \Illuminate\Http\JsonResponse
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
     * 
     * Función para eliminar un usuario por ID (SoftDelete).
     * Valida el ID y elimina el usuario de forma segura.
     * 
     * @param int $id ID del usuario que se va a eliminar
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con un mensaje de éxito o error.
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
     * Función para cambiar el rol de un usuario.
     * Cambiamos el rol a 'usuario' y actualizamos el usuario para que no se liste en la vista de especialistas o pacientes.
     * Ademas se eliminan las citas pendiente que les correspondan.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con un mensaje de éxito o error.
     * @param int $id ID del usuario que se va a cambiar el rol
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
     * Función para obtener todos los usuario de la tabla user cuyo rol sea 'usuario'
     * Este método sirve para listar los usuarios que pueden ser seleccionado para convertirse en 'pacientes' o 'especialistas'
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con los usuarios que tienen el rol de 'usuario' o mensaje de error.
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
