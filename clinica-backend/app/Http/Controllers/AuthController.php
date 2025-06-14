<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Log;
use App\Traits\Loggable;

class AuthController extends Controller
{

    use Loggable;
    /**
     * Manejo de la solicitud de inicio de sesión.
     * Valida las credenciales del usuario y, si son correctas, genera un token de acceso.
     * Si las credenciales son incorrectas, devuelve un mensaje de error.
     *
     * @param Request $solicitud datos de la solicitud HTTP
     * @return \Illuminate\Http\JsonResponse datos del usuario autenticado, token de acceso y código de respuesta HTTP
     * 
     * La función registrarLog está llamada solo si el login es exitoso, pues si falla no hay usuario para identificar.
     */

    public function login(Request $solicitud): JsonResponse
    {
        $solicitud->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $respuesta = [];
        $codigoRespuesta = 200;

        if (!Auth::attempt($solicitud->only('email', 'password'))) {
            $respuesta = ['message' => 'Credenciales inválidas'];
            $codigoRespuesta = 401;
            //Este return debe estar aquí para evitar que se ejecute el resto del código si las credenciales son inválidas.
            return response()->json($respuesta, $codigoRespuesta);  
        }

        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->registrarLog($user->id, 'login', 'users', $user->id);

        $respuesta = [
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'rol' => $user->getRoleNames()->first(),
            ],
        ];

        return response()->json($respuesta, $codigoRespuesta);
    }

    /**
     * Manejo de la solicitud de cierre de sesión.
     * Se eliminan todos los tokens del usuario autenticado. 
     * Si no hay usuario autenticado, se devuelve un mensaje de error.
     * @return \Illuminate\Http\JsonResponse devuelve un mensaje de éxito o error y el código de respuesta HTTP
     */

    public function logout(): JsonResponse
    {
        $codigoRespuesta = 200;
        $respuesta = ['message' => 'Sesión cerrada correctamente'];

        $user = auth()->user();

        if ($user) {
            $user->tokens()->delete();

            $this->registrarLog($user->id, 'logout', 'users', $user->id);
        } else {
            $codigoRespuesta = 401;
            $respuesta = ['message' => 'No autenticado'];
        }

        return response()->json($respuesta, $codigoRespuesta);
    }

    /**
     * Devuelve los datos del usuario autenticado.
     * Para acceder a esta función, el usuario debe estar autenticado, como no se puede acceder sin autenticación,
     * se asume que el usuario existe y se registra el acceso directamente.
     * @param Request $solicitud datos de la solicitud HTTP
     * @return \Illuminate\Http\JsonResponse devuelve los datos del usuario autenticado o un mensaje de error si no hay usuario autenticado
     */
    public function me(Request $solicitud): JsonResponse
    {
        $user = $solicitud->user();

        //Se registrar el acceso directamente, ya que sabemos que el usuario está autenticado
        $this->registrarLog($user->id, 'acceso_me', 'users', $user->id);

        $respuesta = [
            'user' => [
                'id'        => $user->id,
                'nombre'    => $user->nombre,
                'apellidos' => $user->apellidos,
                'email'     => $user->email,
                'rol'       => $user->getRoleNames()->first() ?? null,
            ],
        ];
        return response()->json($respuesta, 200);
    }

    /**
     * Registra un nuevo usuario y emite un token de acceso.
     * Valida los datos proporcionados, crea un nuevo usuario en la base de datos,
     * genera un token de acceso y registra el evento en el log.
     * @param  \Illuminate\Http\Request $request recibe los datos de la solicitud HTTP
     * @throws \Illuminate\Validation\ValidationException si los datos proporcionados no son válidos
     * @throws \Exception si ocurre un error al intentar registrar el usuario
     * @return \Illuminate\Http\JsonResponse Devuelve los datos del usuario registrado, el token de acceso y el código de respuesta HTTP
     */
    public function registrar(Request $request): JsonResponse
    {
        $response = [
            'access_token' => null,
            'token_type' => 'Bearer',
            'user' => null,
        ];
        $statusCode = 201;

        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|min:2|max:50',
                'apellidos' => 'required|string|min:2|max:50',
                'email' => 'required|email|unique:users,email',
                'dni_usuario' => 'required|string|size:9|unique:users,dni_usuario',
                'password' => ['required', 'confirmed', Rules\Password::defaults()], 
            ]);

            $user = User::create([
                'nombre' => $validatedData['nombre'],
                'apellidos' => $validatedData['apellidos'],
                'email' => $validatedData['email'],
                'dni_usuario' => $validatedData['dni_usuario'],
                'password' => Hash::make($validatedData['password']),
            ]);

            //Se asigna el rol "paciente" al nuevo usuario por defecto
            $user->assignRole('paciente');

            $token = $user->createToken('auth_token')->plainTextToken;

            // Usamos el método del Trait para registrar el log
            $this->registrarLog(
                $user->id,
                'Registro de nuevo usuario',
                'users',
                $user->id
            );

            $response['access_token'] = $token;
            $response['user'] = [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'dni_usuario' => $user->dni_usuario,
                'rol' => $user->getRoleNames()->first(),
            ];

        } catch (ValidationException $e) {
            $response = [
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors(),
            ];
            $statusCode = 422;

        } catch (\Exception $e) {
            LaravelLog::error('Error al registrar nuevo usuario: ' . $e->getMessage(), [
                'email' => $request->input('email'),
                'trace' => $e->getTraceAsString(),
            ]);

            $response = [
                'message' => 'Ocurrió un error interno al intentar registrar el usuario. Por favor, inténtalo de nuevo más tarde.',
            ];
            $statusCode = 500;
        }

        return response()->json($response, $statusCode);
    }
}
