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
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


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
        $usuarios = User::all();
        $codigo = 200;
        $respuesta = $usuarios;

        if ($usuarios->isEmpty()) {
            $respuesta = ['message' => 'No hay usuarios registrados'];
            $codigo = 404;
            $this->registrarLog(auth()->id(), 'listar', 'users', null);
        } else {
            $this->registrarLog(auth()->id(), 'listar', 'users', null);
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

        if (!is_numeric($id)) {
            $respuesta = ['mensaje' => 'ID inválido. Debe ser numérico'];
            $codigo = 400;
        } else {
            $usuario = User::find($id);

            if (!$usuario) {
                $respuesta = ['mensaje' => 'Usuario no encontrado'];
                $codigo = 404;
            } else {
                $respuesta = $usuario;
            }

            $this->registrarLog(auth()->id(), 'ver_usuario', 'users', $id);
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

        $validador = Validator::make($solicitud->all(), [
            'nombre'     => 'required|string|max:255',
            'apellidos'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6|confirmed',
            'dni_usuario' => 'required|string|max:9|unique:users,dni_usuario',
        ]);

        if ($validador->fails()) {
            $respuesta = ['errores' => $validador->errors()];
            $codigo = 422;
        } else {
            $usuario = User::create([
                'nombre'     => $solicitud->input('nombre'),
                'apellidos'  => $solicitud->input('apellidos'),
                'email'      => $solicitud->input('email'),
                'password'   => Hash::make($solicitud->input('password')),
                'dni_usuario' => $solicitud->input('dni_usuario'),
            ]);
            $usuario->assignRole('paciente');
            $respuesta = $usuario->load('roles');

            $this->registrarLog(auth()->id(), 'crear', 'users', $usuario->id);
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
        $respuesta = [];
        $codigo = 200;

        if (!is_numeric($id)) {
            $respuesta = ['mensaje' => 'ID inválido'];
            $codigo = 400;
        } else {
            $usuario = User::find($id);

            if (!$usuario) {
                $respuesta = ['mensaje' => 'Usuario no encontrado'];
                $codigo = 404;
            } else {
                // Validar datos, incluyendo validación condicional de password
                $reglas = [
                    'nombre'     => 'required|string|max:255',
                    'apellidos'  => 'required|string|max:255',
                    'email'      => 'required|email|unique:users,email,' . $usuario->id,
                    'dni_usuario'=> 'required|string|max:9|unique:users,dni_usuario,' . $usuario->id,
                    'fecha_nacimiento' => 'nullable|date',
                    'telefono'   => 'nullable|string|max:20',
                    'direccion'  => 'nullable|string|max:255',
                ];

                // Si viene password, validar su confirmación
                if ($solicitud->filled('password')) {
                    $reglas['password'] = 'string|min:6|confirmed';
                }

                $validador = Validator::make($solicitud->all(), $reglas);

                if ($validador->fails()) {
                    return response()->json(['errores' => $validador->errors()], 422);
                }

                $usuario->nombre = $solicitud->input('nombre');
                $usuario->apellidos = $solicitud->input('apellidos');
                $usuario->email = $solicitud->input('email');
                $usuario->dni_usuario = $solicitud->input('dni_usuario');
                $usuario->fecha_nacimiento = $solicitud->input('fecha_nacimiento');
                $usuario->telefono = $solicitud->input('telefono');
                $usuario->direccion = $solicitud->input('direccion');

                if ($solicitud->filled('password')) {
                    $usuario->password = Hash::make($solicitud->input('password'));
                }

                $usuario->save();

                $respuesta = User::find($usuario->id);

                $this->registrarLog(auth()->id(), 'actualizar_usuario', 'users', $usuario->id);
            }
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
        $respuesta = [];
        $codigo = 200;

        if (!is_numeric($id)) {
            $respuesta = ['mensaje' => 'ID inválido'];
            $codigo = 400;
        } else {
            $usuario = User::find($id);

            if (!$usuario) {
                $respuesta = ['mensaje' => 'Usuario no encontrado'];
                $codigo = 404;
            } else {
                $usuario->delete();
                $respuesta = ['mensaje' => 'Usuario eliminado correctamente'];

                $this->registrarLog(auth()->id(), 'eliminar_usuario', 'users', $id);
            }
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
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json(['mensaje' => 'El usuario no existe'], 404);
        }

        if ($usuario->hasRole('especialista')) {
            //Se elimina de la tabla especialistas
            Especialista::where('user_id', $usuario->id)->delete();

            //Se eliminan citas pendientes como especialista
            Cita::where('especialista_id', $usuario->id)
                ->where('estado', 'pendiente')
                ->delete();

            \Log::info("Usuario con ID {$usuario->id} cambió rol de 'especialista' a 'usuario'. Citas pendientes eliminadas.");
        }

        if ($usuario->hasRole('paciente')) {
            //Se elimina de la tabla pacientes
            Paciente::where('user_id', $usuario->id)->delete();

            //Se eliminan citas pendientes como paciente
            Cita::where('paciente_id', $usuario->id)
                ->where('estado', 'pendiente')
                ->delete();

            \Log::info("Usuario con ID {$usuario->id} cambió rol de 'paciente' a 'usuario'. Citas pendientes eliminadas.");
        }


        $usuario->syncRoles(['usuario']);

        return response()->json(['mensaje' => 'El rol del usuario ha sido cambiado a usuario y sus citas pendientes han sido eliminadas.'], 200);
    }


    /**
     * Funcion para obtener todos los usuario de la tabla user cuyo rol sea 'usuario'
     * Este metodo sirve para listar los usuarios que pueden ser seleccionado para convertirse en 'pacientes' o 'especialistas'
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con los usuarios que tienen el rol de 'usuario' o mensaje de error.
     */
    public function getUsuariosSinRolEspecialistaNiPaciente(): JsonResponse
    {
        if (!auth()->check() || !auth()->user()->hasRole('administrador')) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $usuarios = DB::table('users')
            ->leftJoin('pacientes', 'users.id', '=', 'pacientes.user_id')
            ->leftJoin('especialistas', 'users.id', '=', 'especialistas.user_id')
            ->whereNull('pacientes.user_id')
            ->whereNull('especialistas.user_id')
            ->select('users.id', 'users.nombre', 'users.apellidos')
            ->get()
            ->map(function ($user) {
                return [
                    'id'               => $user->id,
                    'nombre_apellidos' => $user->nombre . ' ' . $user->apellidos,
                ];
            });

        $this->registrarLog(auth()->id(), 'listar_usuarios_para_asignar_especialista', 'usuarios', null);

        return response()->json($usuarios);
    }


}
