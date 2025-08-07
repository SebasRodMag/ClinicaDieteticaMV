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
     * 
     * Función para crear un nuevo usuario.
     * Valida los datos de entrada y crea un nuevo usuario en la base de datos.
     * 
     * @param Request $solicitud datos del usuario nuevo
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con los datos del usuario creado o un mensaje de error.
     * @throws \Illuminate\Validation\ValidationException si la validación falla.
     */
    public function crearUsuario(Request $solicitud): JsonResponse
    {
        $codigo = 201;
        $respuesta = [];

        $validar = Validator::make($solicitud->all(), [
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
            try {
                $usuario = User::create([
                    'nombre' => $solicitud->input('nombre'),
                    'apellidos' => $solicitud->input('apellidos'),
                    'email' => $solicitud->input('email'),
                    'password' => Hash::make($solicitud->input('password')),
                    'dni_usuario' => $solicitud->input('dni_usuario'),
                ]);

                //Asignar el rol a usuario.
                $usuario->assignRole('paciente');

                // Crear el modelo Paciente
                $paciente = Paciente::create([
                    'user_id' => $usuario->id,
                    'numero_historial' => $this->generarNumeroHistorialUnico(),
                    'fecha_alta' => now(),
                ]);

                // Determinar quién crea al paciente (Administrador o Especialista)
                $especialistaNombre = auth()->user()?->nombre ?? 'uno de nuestros especialistas';

                // Notificar al nuevo paciente por email
                Notification::send($usuario, new PacienteAltaNotificacion(
                    nombreEspecialista: $especialistaNombre,
                    numeroHistorial: $paciente->numero_historial,
                ));

                $this->registrarLog(auth()->id(), 'crear_usuario', 'users', $usuario->id);

                $respuesta = $usuario->load('roles');
            } catch (QueryException $e) {
                $codigo = 500;
                $respuesta = [
                    'errors' => [
                        'general' => ['Error en la base de datos. Revisa que el email o DNI no estén duplicados.']
                    ]
                ];
                $this->logError(auth()->id(), 'Error al crear usuario (DB)', $e->getMessage());
            } catch (\Exception $e) {
                $codigo = 500;
                $respuesta = [
                    'errors' => [
                        'general' => ['Ocurrió un error inesperado al crear el usuario.']
                    ]
                ];
                $this->logError(auth()->id(), 'Error inesperado al crear usuario', $e->getMessage());
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
            $codigo = 400;
            $respuesta = ['errors' => ['id' => ['El ID proporcionado no es válido.']]];
            return response()->json($respuesta, $codigo);
        }

        $usuario = User::find($id);

        if (!$usuario) {
            $codigo = 404;
            $respuesta = ['errors' => ['general' => ['El usuario no existe.']]];
            return response()->json($respuesta, $codigo);
        }

        try {
            if ($usuario->hasRole('especialista')) {
                Especialista::where('user_id', $usuario->id)->delete();

                Cita::where('especialista_id', $usuario->id)
                    ->where('estado', 'pendiente')
                    ->delete();

                \Log::info("El usuario {$usuario->id} dejó de ser especialista. Se eliminaron citas pendientes.");
            }

            if ($usuario->hasRole('paciente')) {
                Paciente::where('user_id', $usuario->id)->delete();

                Cita::where('paciente_id', $usuario->id)
                    ->where('estado', 'pendiente')
                    ->delete();

                \Log::info("El usuario {$usuario->id} dejó de ser paciente. Se eliminaron citas pendientes.");
            }

            $usuario->syncRoles(['usuario']);

            $this->registrarLog(auth()->id(), 'actualizar_usuario', 'users', $usuario->id);

            $respuesta = ['mensaje' => 'El rol del usuario ha sido cambiado a "usuario" y se eliminaron sus citas pendientes.'];

        } catch (QueryException $e) {
            $codigo = 500;
            $respuesta = ['errors' => ['general' => ['Error al cambiar el rol del usuario.']]];
            $this->logError(auth()->id(), 'Error DB al cambiar rol del usuario', $e->getMessage());

        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = ['errors' => ['general' => ['Ocurrió un error inesperado al cambiar el rol del usuario.']]];
            $this->logError(auth()->id(), 'Error inesperado al cambiar rol del usuario', $e->getMessage());
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



}
