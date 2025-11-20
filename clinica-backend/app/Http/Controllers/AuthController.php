<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Traits\Loggable;
use App\Models\Paciente;
use OpenApi\Anotations as OA;

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

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Iniciar sesión",
     *     description="Valida las credenciales del usuario y devuelve un token de acceso junto con la información básica del usuario.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@clinicamv.lol"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Inicio de sesión correcto.",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nombre", type="string"),
     *                 @OA\Property(property="apellidos", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="rol", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Credenciales inválidas."),
     *     @OA\Response(response=422, description="Datos de entrada no válidos."),
     *     @OA\Response(response=500, description="Error interno del servidor.")
     * )
     */



    public function login(Request $solicitud): JsonResponse
    {
        $codigoRespuesta = 200;
        $respuesta = [];

        try {
            $solicitud->validate([
                'email' => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'Formato de correo inválido.',
                'password.required' => 'La contraseña es obligatoria.',
            ]);

            $user = User::where('email', $solicitud->input('email'))->first();

            if (!$user || !Hash::check($solicitud->input('password'), $user->password)) {
                //Login fallido
                $codigoRespuesta = 401;
                $respuesta = ['message' => 'Credenciales inválidas'];

                $this->logError(null, 'Intento fallido de login', [
                    'email' => $solicitud->input('email'),
                    'ip' => $solicitud->ip(),
                    'user_agent' => $solicitud->userAgent(),
                ]);

                return response()->json($respuesta, $codigoRespuesta);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $this->registrarLog($user->id, 'login', 'users', $user->id);

            return response()->json([
                'access_token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'apellidos' => $user->apellidos,
                    'email' => $user->email,
                    'rol' => $user->getRoleNames()->first(),
                ],
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            $this->logError(null, 'Error inesperado en login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Ha ocurrido un error inesperado al intentar iniciar sesión.',
            ], 500);
        }
    }


    /**
     * Manejo de la solicitud de cierre de sesión de usuario autenticado.
     * Se eliminan todos los tokens del usuario autenticado. 
     * Si no hay usuario autenticado, se devuelve un mensaje de error.
     * @return \Illuminate\Http\JsonResponse devuelve un mensaje de éxito o error y el código de respuesta HTTP
     */

    public function logout(): JsonResponse
    {
        $codigoRespuesta = 200;
        $respuesta = ['message' => 'Sesión cerrada correctamente'];

        try {
            $user = auth()->user();

            if ($user) {
                $user->tokens()->delete(); //Se revoca todos los tokens
                $this->registrarLog($user->id, 'logout', 'users', $user->id);
            } else {
                $codigoRespuesta = 401;
                $respuesta = ['message' => 'No autenticado'];
            }

        } catch (\Exception $e) {
            $codigoRespuesta = 500;
            $respuesta = ['message' => 'Error inesperado al cerrar sesión'];

            $this->logError(null, 'Error inesperado en logout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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

        $this->registrarLog($user->id, 'acceso_me', 'users', $user->id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'rol' => $user->getRoleNames()->first(),
            ],
        ], 200);
    }

    /**
     * Registra un nuevo usuario y emite un token de acceso.
     * Valida los datos proporcionados, crea un nuevo usuario en la base de datos,
     * genera un token de acceso y registra el evento en el log.
     * @param  \Illuminate\Http\Request $solicitud recibe los datos de la solicitud HTTP
     * @throws \Illuminate\Validation\ValidationException si los datos proporcionados no son válidos
     * @throws \Exception si ocurre un error al intentar registrar el usuario
     * @return \Illuminate\Http\JsonResponse Devuelve los datos del usuario registrado, el token de acceso y el código de respuesta HTTP
     */
    public function registrar(Request $solicitud): JsonResponse
    {
        $codigo = 201;
        $respuesta = [
            'access_token' => null,
            'token_type' => 'Bearer',
            'user' => null,
        ];

        try {
            $datos = $solicitud->validate([
                'nombre' => 'required|string|min:2|max:50',
                'apellidos' => 'required|string|min:2|max:50',
                'email' => 'required|email|unique:users,email',
                'dni_usuario' => 'required|string|size:9|unique:users,dni_usuario',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ], [
                'email.unique' => 'El correo electrónico ya está registrado.',
                'dni_usuario.unique' => 'El DNI ya está registrado.',
                'nombre.required' => 'El nombre es obligatorio.',
                'apellidos.required' => 'El apellido es obligatorio.',
                'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
                'apellidos.min' => 'El apellido debe tener al menos 2 caracteres.',
            ]);

            $user = User::create([
                'nombre' => $datos['nombre'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'],
                'dni_usuario' => $datos['dni_usuario'],
                'password' => Hash::make($datos['password']),
            ]);

            $user->assignRole('paciente');
            $token = $user->createToken('auth_token')->plainTextToken;

            // Se crea la entrada en la tabla 'pacientes'
            Paciente::create([
                'user_id' => $user->id,
                'numero_historial' => $this->generarNumeroHistorialUnico(),
                'fecha_alta' => now(),
            ]);

            $this->registrarLog($user->id, 'registro', 'users', $user->id);

            $respuesta['access_token'] = $token;
            $respuesta['user'] = [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'dni_usuario' => $user->dni_usuario,
                'rol' => $user->getRoleNames()->first(),
            ];

        } catch (ValidationException $e) {
            $codigo = 422;
            $respuesta = [
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors(),
            ];

        } catch (\Exception $e) {
            $codigo = 500;
            $respuesta = [
                'message' => 'Ocurrió un error interno al intentar registrar el usuario.',
            ];

            $this->logError(null, 'Error inesperado al registrar usuario', [
                'email' => $solicitud->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json($respuesta, $codigo);
    }
}
