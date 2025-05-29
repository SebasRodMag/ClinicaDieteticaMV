<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{

    /**
     * Acciones válidas permitidas para filtrado de logs.
     */
    private const ACCIONES_VALIDAS = [
        'login',
        'logout',
        'crear_cita',
        'actualizar_usuario',
    ];
    
    /**
     * 
     * Constructor para aplicar middleware de autenticación y rol.
     * 
     */
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:administrador']);
    }

    /**
     * 
     * Listar todos los logs.
     * Se obtienen todos los logs de la base de datos, ordenados por fecha de creación.
     * Se incluye la relación con el usuario que generó el log para obtener su información.
     * @return \Illuminate\Http\JsonResponse devuelve una respuesta JSON con todos los logs.
     * @throws \Exception si ocurre un error al consultar los logs.
     */
    public function listarLogs()
    {
        try {
            $logs = Log::with('usuario:id,nombre,apellidos,email')
                        ->orderBy('created_at', 'desc')
                        ->get();

            return response()->json(['data' => $logs], 200);
        } catch (\Exception $e) {
            \Log::error('Error al listar logs: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error al obtener los logs.',
            ], 500);
        }
    }

    /**
     * 
     * Obtener logs por ID de usuario.
     * Se busca un usuario por su ID y se devuelven sus logs.
     * Se valida que el ID sea numérico y se maneja el caso en que no es válido.
     * @param int $id ID del usuario
     * @return \Illuminate\Http\JsonResponse devuelve los logs del usuario o un mensaje de error si el ID no es válido.
     * @throws \Exception si ocurre un error al consultar los logs.
     */
    public function porUsuario($id): JsonResponse
    {
        if (!is_numeric($id)) {
            return response()->json([
                'error' => 'ID inválido',
            ], 400);
        }

        try {
            $logs = Log::with('usuario:id,nombre,apellidos,email')
                        ->where('usuario_id', $id)
                        ->orderBy('created_at', 'desc')
                        ->get();

            \Log::info("Consulta de logs del usuario ID {$id}");

            return response()->json(['data' => $logs], 200);
        } catch (\Exception $e) {
            \Log::error("Error al obtener logs del usuario ID {$id}: " . $e->getMessage());

            return response()->json([
                'error' => 'Error al obtener los logs del usuario.',
            ], 500);
        }
    }

    /**
     * 
     * Obtener logs por acción específica.
     * Se filtran los logs por una acción específica.
     * Se valida que la acción sea una de las acciones permitidas.
     * 
     * @param string $accion Acción a filtrar
     * @return \Illuminate\Http\JsonResponse devuelve los logs filtrados por acción o un mensaje de error si la acción no es válida.
     * @throws \Exception si ocurre un error al consultar los logs.
     */
    public function porAccion($accion)
    {
        if (!in_array($accion, self::ACCIONES_VALIDAS)) {
            return response()->json([
                'error' => 'Acción no válida',
            ], 400);
        }

        try {
            $logs = Log::with('usuario:id,nombre,apellidos,email')
                        ->where('accion', $accion)
                        ->orderBy('created_at', 'desc')
                        ->get();

            \Log::info("Consulta de logs por acción: {$accion}");

            return response()->json(['data' => $logs], 200);
        } catch (\Exception $e) {
            \Log::error("Error al obtener logs por acción {$accion}: " . $e->getMessage());

            return response()->json([
                'error' => 'Error al obtener los logs por acción.',
            ], 500);
        }
    }

}
