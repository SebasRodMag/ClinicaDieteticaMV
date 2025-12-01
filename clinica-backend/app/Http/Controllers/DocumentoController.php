<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Documento;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Historial;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Traits\Loggable;
use OpenApi\Annotations as OA;


class DocumentoController extends Controller
{


    use Loggable;
    /**
     * Listar documentos según el rol del usuario autenticado.
     *
     * - Administrador: ve todos los documentos.
     * - Especialista: ve documentos de pacientes asignados.
     * - Paciente: ve solo sus documentos.
     *
     * RUTA:
     *  GET /documentos
     * ROLES:
     *  administrador | especialista | paciente
     *
     * @OA\Get(
     *   path="/documentos",
     *   summary="Listar documentos según el rol",
     *   description="Devuelve la lista de documentos visible para el usuario autenticado, dependiendo de su rol.",
     *   tags={"Documentos"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Documentos listados correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="documentos",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Documento")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No hay documentos disponibles",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No hay documentos disponibles")
     *     )
     *   )
     * )
     *
     * @return JsonResponse devuelve una respuesta JSON con los documentos o un mensaje de error si no hay documentos disponibles.
     */
    public function listarDocumentos(): JsonResponse
    {
        $user = auth()->user();
        $respuesta = [];
        $codigo = 200;

        if ($user->hasRole('administrador')) {
            $documentos = Documento::with('historial.paciente')->get();
        } elseif ($user->hasRole('especialista')) {
            //Documentos asociados a pacientes del especialista
            $documentos = Documento::whereHas('historial.paciente.citas', function ($query) use ($user) {
                $query->where('especialista_id', $user->especialista->id);
            })->with('historial.paciente')->get();
        } else {
            //El paciente solo ve sus documentos
            $documentos = Documento::whereHas('historial', function ($q) use ($user) {
                $q->where('paciente_id', $user->paciente->id);
            })->get();
        }

        if ($documentos->isEmpty()) {
            $this->registrarLog($user->id, 'listar_documentos', 'No hay documentos disponibles');
            $respuesta = ['message' => 'No hay documentos disponibles'];
            $codigo = 404;
        } else {
            $this->registrarLog($user->id, 'listar_documentos', 'Documentos listados correctamente');
            $respuesta = ['documentos' => $documentos];
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Ver un documento específico según el ID.
     *
     * Controla el acceso según el rol del usuario:
     * - Administrador: acceso total.
     * - Especialista: acceso a documentos de sus pacientes.
     * - Paciente: acceso solo a sus propios documentos.
     *
     * RUTA:
     *  GET /documentos/{id}
     *
     * @OA\Get(
     *   path="/documentos/{id}",
     *   summary="Ver un documento",
     *   description="Devuelve un documento concreto si el usuario tiene permisos para verlo.",
     *   tags={"Documentos"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del documento",
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Documento encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="documento",
     *         ref="#/components/schemas/Documento"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado para ver este documento",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No autorizado para ver este documento")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Documento no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Documento no encontrado")
     *     )
     *   )
     * )
     *
     * @param int $id ID del documento que se desea ver
     *
     * @return JsonResponse Devuelve una respuesta JSON con el documento o un mensaje de error si no se encuentra o no está autorizado.
     */
    public function verDocumento(int $id): JsonResponse
    {
        $user = auth()->user();
        $respuesta = [];
        $codigo = 200;

        $documento = Documento::with('historial.paciente')->find($id);

        if (!$documento) {
            $this->registrarLog($user->id, 'ver_documento_fallido', "Documento ID $id no encontrado");
            $respuesta = ['message' => 'Documento no encontrado'];
            $codigo = 404;
        } else {
            $acceso = false;

            if ($user->hasRole('administrador')) {
                $acceso = true;
            } elseif ($user->hasRole('especialista')) {
                //Un especialista puede ver documentos de sus pacientes
                $especialistaId = $user->especialista->id ?? null;
                if (
                    $documento->historial && $documento->historial->paciente->citas()
                        ->where('especialista_id', $especialistaId)->exists()
                ) {
                    $acceso = true;
                }
            } else {
                //El paciente solo puede ver sus documentos
                $pacienteId = $user->paciente->id ?? null;
                if ($documento->historial && $documento->historial->paciente_id == $pacienteId) {
                    $acceso = true;
                }
            }

            if ($acceso) {
                $this->registrarLog($user->id, 'ver_documento', "Documento ID $id visto");
                $respuesta = ['documento' => $documento];
            } else {
                $this->registrarLog($user->id, 'ver_documento_no_autorizado', "Acceso denegado documento ID $id");
                $respuesta = ['message' => 'No autorizado para ver este documento'];
                $codigo = 403;
            }
        }

        return response()->json($respuesta, $codigo);
    }


    /**
     * Eliminar un documento si el usuario es su propietario o es administrador.
     *
     * Controla el acceso según el rol del usuario:
     * - Administrador: acceso total.
     * - Especialista: NO puede eliminar (por diseño actual).
     * - Paciente: solo puede eliminar sus propios documentos.
     *
     * RUTA:
     *  DELETE /documentos/{id}
     *
     * @OA\Delete(
     *   path="/documentos/{id}",
     *   summary="Eliminar un documento",
     *   description="Elimina un documento si el usuario autenticado es el propietario o tiene rol administrador.",
     *   tags={"Documentos"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del documento a eliminar",
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Documento eliminado correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Documento eliminado correctamente")
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
     *     response=403,
     *     description="No tienes permiso para eliminar este documento",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No tienes permiso para eliminar este documento")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Documento no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Documento no encontrado")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al eliminar el documento",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al eliminar el documento")
     *     )
     *   )
     * )
     *
     * @param int $id ID del documento a eliminar
     *
     * @return JsonResponse devuelve una respuesta JSON con el estado de la operación.
     * @throws \Exception lanza una excepción si ocurre un error al eliminar el documento.
     */
    public function eliminarDocumento(int $id): \Illuminate\Http\JsonResponse
    {
        $codigo = 200;
        $payload = [];
        $userId = auth()->id();

        try {
            // Validación de ID (por si el tipo no viene forzado por la ruta)
            if (!is_numeric($id)) {
                $this->registrarLog($userId, 'eliminar_documento_fallido', "ID inválido (no numérico): $id");
                $codigo = 400;
                $payload = ['message' => 'ID inválido'];
            } else {
                $documento = Documento::find($id);

                if (!$documento) {
                    $this->registrarLog($userId, 'eliminar_documento_fallido', "Documento ID $id no encontrado");
                    $codigo = 404;
                    $payload = ['message' => 'Documento no encontrado'];
                } else {
                    $usuario = auth()->user();
                    $esPropietario = $documento->user_id === $userId;
                    $esAdmin = $usuario->hasRole('administrador');

                    if (!$esPropietario && !$esAdmin) {
                        // solo propietario o admin pueden eliminar
                        $this->registrarLog($userId, 'eliminar_documento_denegado', "Acceso denegado a documento ID $id");
                        $codigo = 403;
                        $payload = ['message' => 'No tienes permiso para eliminar este documento'];
                    } else {
                        //Intentamos borrar el archivo físico si existe
                        $disk = Storage::disk('public');
                        $archivoExiste = $documento->archivo && $disk->exists($documento->archivo);

                        if ($archivoExiste) {
                            try {
                                $disk->delete($documento->archivo);
                            } catch (\Throwable $e) {
                                //Si falla el borrado físico, se registra pero continuamos con el borrado lógico
                                $this->registrarLog(
                                    $userId,
                                    'eliminar_documento_aviso',
                                    "No se pudo borrar el archivo físico para documento ID $id: {$e->getMessage()}"
                                );
                            }
                        } else {
                            $this->registrarLog(
                                $userId,
                                'eliminar_documento_aviso',
                                "Archivo físico no encontrado para documento ID $id"
                            );
                        }

                        $documento->delete();

                        $this->registrarLog($userId, 'eliminar_documento', "Documento ID $id eliminado");
                        $payload = ['message' => 'Documento eliminado correctamente'];
                    }
                }
            }
        } catch (\Throwable $e) {
            $codigo = 500;
            $payload = ['message' => 'Error al eliminar el documento'];
            $this->registrarLog($userId, 'eliminar_documento_error', "Error eliminando documento ID $id: {$e->getMessage()}");
        }

        return response()->json($payload, $codigo);
    }

    /**
     * Listar los documentos del usuario autenticado (si es paciente)
     * o de sus pacientes (si es especialista). El administrador ve todos.
     *
     * RUTA:
     *  GET /mis-documentos
     *
     * @OA\Get(
     *   path="/mis-documentos",
     *   summary="Listar documentos del usuario o de sus pacientes",
     *   description="Devuelve los documentos asociados al usuario autenticado (paciente) o a sus pacientes (especialista). El administrador ve todos.",
     *   tags={"Documentos"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Documentos encontrados (o lista vacía)",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="documentos",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Documento")
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         nullable=true,
     *         example="No se encontraron documentos"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Acceso no autorizado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Acceso no autorizado")
     *     )
     *   )
     * )
     *
     * @return JsonResponse devuelve una respuesta JSON con los documentos del usuario o un mensaje de error si no hay documentos disponibles.
     */
    public function listarMisDocumentos(): JsonResponse
    {
        $usuario = auth()->user();
        $documentos = collect();

        if ($usuario->hasRole('paciente')) {
            $documentos = Documento::where('user_id', $usuario->id)->get();

        } elseif ($usuario->hasRole('especialista')) {
            $pacientesIds = $usuario->especialista->pacientes()->pluck('users.id');
            $documentos = Documento::whereIn('user_id', $pacientesIds)->get();

        } elseif ($usuario->hasRole('administrador')) {
            $documentos = Documento::all();

        } else {
            $this->registrarLog($usuario->id, 'listar_documentos_denegado', 'Rol no autorizado');
            return response()->json(['message' => 'Acceso no autorizado'], 403);
        }

        if ($documentos->isEmpty()) {
            $this->registrarLog($usuario->id, 'listar_documentos', 'No hay documentos para mostrar');
            return response()->json([
                'message' => 'No se encontraron documentos',
                'documentos' => [],
            ], 200);
        }

        $this->registrarLog($usuario->id, 'listar_documentos', 'Listado de documentos consultado');
        return response()->json(['documentos' => $documentos], 200);
    }


    /**
     * Subir un nuevo documento.
     *
     * Esta función permite a un usuario subir un documento asociado (o no) a un historial médico.
     *
     * Validaciones:
     * - nombre: requerido, string, máx. 255 caracteres
     * - archivo: requerido, PDF/JPG/JPEG/PNG, máx. 5 MB
     * - historial_id: opcional, debe existir en la tabla `historials`
     *
     * RUTA:
     *  POST /documentos
     *
     * @OA\Post(
     *   path="/documentos",
     *   summary="Subir un documento",
     *   description="Permite subir un archivo (pdf/jpg/jpeg/png) asociado opcionalmente a un historial médico.",
     *   tags={"Documentos"},
     *   security={{"sanctum":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     description="Datos del documento a subir",
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"nombre", "archivo"},
     *         @OA\Property(
     *           property="nombre",
     *           type="string",
     *           maxLength=255,
     *           example="Analítica de sangre"
     *         ),
     *         @OA\Property(
     *           property="descripcion",
     *           type="string",
     *           nullable=true,
     *           example="Analítica de sangre del 2025-04-10"
     *         ),
     *         @OA\Property(
     *           property="archivo",
     *           type="string",
     *           format="binary",
     *           description="Archivo a subir (pdf, jpg, jpeg, png)"
     *         ),
     *         @OA\Property(
     *           property="historial_id",
     *           type="integer",
     *           nullable=true,
     *           example=3
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Documento subido correctamente",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Documento subido correctamente"),
     *       @OA\Property(property="documento", ref="#/components/schemas/Documento")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Errores de validación",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"nombre": {"El campo nombre es obligatorio."}}
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Error al subir el documento",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Error al subir el documento")
     *     )
     *   )
     * )
     *
     * @param Request $solicitud contiene los datos del documento a crear
     * @return JsonResponse devuelve una respuesta JSON con el estado de la operación.
     * @throws \Illuminate\Validation\ValidationException si los datos no cumplen con las reglas de validación.
     * @throws \Exception lanza una excepción si ocurre un error al subir el documento.
     */
    public function subirDocumento(Request $solicitud): JsonResponse
    {
        $respuesta = [];
        $codigo = 201;

        $validar = Validator::make($solicitud->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', //5MB máx
            'historial_id' => 'nullable|exists:historials,id',
        ]);
        \Log::info('Usuario autenticado', [
            'id' => auth()->id(),
            'roles' => auth()->user()->getRoleNames()
        ]);

        if ($validar->fails()) {
            $respuesta = ['errors' => $validar->errors()];
            $codigo = 422;
        } else {
            try {
                $archivo = $solicitud->file('archivo');
                $ruta = $archivo->store('documentos', 'public');

                $documento = Documento::create([
                    'user_id' => auth()->id(),
                    'historial_id' => $solicitud->historial_id,
                    'nombre' => $solicitud->nombre,
                    'descripcion' => $solicitud->descripcion,
                    'archivo' => $ruta,
                    'tipo' => $archivo->getClientMimeType(),
                    'tamano' => $archivo->getSize(),
                ]);

                $this->registrarLog(auth()->id(), 'subir_documento', "Documento ID {$documento->id} subido");

                $respuesta = [
                    'message' => 'Documento subido correctamente',
                    'documento' => $documento,
                ];
            } catch (\Exception $e) {
                $this->registrarLog(auth()->id(), 'subir_documento_error', "Error al subir documento: {$e->getMessage()}");
                $respuesta = ['message' => 'Error al subir el documento'];
                $codigo = 500;
                $this->logError(
                    auth()->id(),
                    'Error al subir documento',
                    [
                        'mensaje' => $e->getMessage(),
                        'archivo' => $e->getFile(),
                        'linea' => $e->getLine(),
                        'traza' => $e->getTraceAsString(),
                        'input' => $solicitud->all(),
                    ]
                );
            }
        }

        return response()->json($respuesta, $codigo);
    }



    /**
     * Descargar un documento por su ID.
     *
     * Controla el acceso según el rol del usuario:
     * - Administrador: acceso total.
     * - Especialista: acceso a documentos de pacientes asignados.
     * - Paciente: acceso solo a sus propios documentos.
     *
     * RUTA:
     *  GET /documentos/{id}/descargar
     *
     * @OA\Get(
     *   path="/documentos/{id}/descargar",
     *   summary="Descargar un documento",
     *   description="Permite descargar el archivo físico de un documento, si el usuario tiene permisos.",
     *   tags={"Documentos"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID del documento a descargar",
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Documento descargado correctamente (respuesta binaria)",
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No tienes permiso para descargar este documento",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No tienes permiso para descargar este documento")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Documento o archivo no encontrado",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Documento no encontrado")
     *     )
     *   )
     * )
     *  
     * @param int $id ID del documento a descargar
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse devuelve una respuesta JSON con el estado de la operación o un archivo descargable.
     * @throws \Exception lanza una excepción si ocurre un error al descargar el documento.
     */
    public function descargarDocumento(int $id): JsonResponse|StreamedResponse
    {
        $codigo = 200;
        $respuesta = null; //puede ser JSON o StreamedResponse

        $documento = Documento::find($id);

        if (!$documento) {
            $this->registrarLog(auth()->id(), 'descargar_documento_fallido', "Documento ID $id no encontrado");
            $codigo = 404;
            $respuesta = response()->json(['message' => 'Documento no encontrado'], $codigo);
        } else {
            $usuario = auth()->user();
            $esPropietario = $documento->user_id === $usuario->id;
            $esEspecialistaRelacionado = false;

            if ($usuario->hasRole('especialista')) {
                $especialista = $usuario->especialista;
                if ($especialista) {
                    $pacienteId = Paciente::where('user_id', $documento->user_id)->value('id');
                    $esEspecialistaRelacionado = $pacienteId
                        ? $especialista->citas()
                            ->where('id_paciente', $pacienteId)
                            ->whereIn('estado', ['pendiente', 'confirmada', 'finalizada'])
                            ->exists()
                        : false;
                }
            }

            if (!$esPropietario && !$usuario->hasRole('administrador') && !$esEspecialistaRelacionado) {
                $this->registrarLog($usuario->id, 'descargar_documento_denegado', "Acceso denegado a documento ID $id");
                $codigo = 403;
                $respuesta = response()->json(['message' => 'No tienes permiso para descargar este documento'], $codigo);
            } elseif (!Storage::disk('public')->exists($documento->archivo)) {
                $this->registrarLog($usuario->id, 'descargar_documento_fallido', "Archivo físico no encontrado para documento ID $id");
                $codigo = 404;
                $respuesta = response()->json(['message' => 'Archivo no encontrado en el servidor'], $codigo);
            } else {
                $this->registrarLog($usuario->id, 'descargar_documento', "Descarga del documento ID $id");
                $respuesta = Storage::disk('public')->download(
                    $documento->archivo,
                    $documento->nombre ?? basename($documento->archivo)
                );
            }
        }

        return $respuesta;
    }

    /**
     * Recuperar los documentos de un paciente específico recibiendo su ID.
     *
     * Solo administrador o especialista pueden consultar.
     * Si es especialista, se filtra (en la query) a documentos que estén marcados como visibles para él.
     *
     * RUTA:
     *  GET /pacientes/{paciente}/documentos
     *
     * @OA\Get(
     *   path="/pacientes/{paciente}/documentos",
     *   summary="Listar documentos de un paciente",
     *   description="Devuelve los documentos asociados a un paciente concreto. Solo accesible para administrador o especialista.",
     *   tags={"Documentos"},
     *   security={{"sanctum":{}}},
     *   @OA\Parameter(
     *     name="paciente",
     *     in="path",
     *     required=true,
     *     description="ID del paciente",
     *     @OA\Schema(type="integer", example=5)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Documentos del paciente listados correctamente (o lista vacía)",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="documentos",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Documento")
     *       ),
     *       @OA\Property(property="message", type="string", nullable=true)
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="No autorizado para ver estos documentos",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No autorizado para ver estos documentos")
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
     * @param int $pacienteId ID del paciente
     *
     * @return JsonResponse
     */
    public function obtenerDocumentosPorPaciente(int $pacienteId): JsonResponse
    {
        $user = auth()->user();
        $codigo = 200;
        $respuesta = [];

        // Solo admin o especialista pueden consultar
        if (!$user->hasRole('administrador') && !$user->hasRole('especialista')) {
            $codigo = 403;
            $respuesta = ['message' => 'No autorizado para ver estos documentos'];
            $this->registrarLog($user->id, 'obtener_documentos_paciente_denegado', "Acceso denegado a documentos del paciente ID $pacienteId");

            return response()->json($respuesta, $codigo);
        }

        // Buscar paciente y su user_id
        $paciente = Paciente::select('id', 'user_id')->find($pacienteId);
        if (!$paciente) {
            $codigo = 404;
            $respuesta = ['message' => 'Paciente no encontrado'];
            $this->registrarLog($user->id, 'obtener_documentos_paciente', "Paciente ID $pacienteId no encontrado");

            return response()->json($respuesta, $codigo);
        }

        // (Esto no esta implementado, pero cabía la posibilidad que un especialista comparta documentos con paciente) si es especialista, validar que tenga relación con el paciente
        // por ejemplo, que exista al menos una cita entre ambos
        // if ($user->hasRole('especialista')) {
        //     $tieneRelacion = $user->especialista?->citas()
        //         ->where('id_paciente', $pacienteId)
        //         ->exists();
        //     if (!$tieneRelacion) {
        //         $codigo = 403;
        //         $respuesta = ['message' => 'No autorizado para ver documentos de este paciente'];
        //         $this->registrarLog($user->id, 'obtener_documentos_paciente_denegado', "Especialista sin relación con paciente ID $pacienteId");
        //         return response()->json($respuesta, $codigo);
        //     }
        // }

        //Query para obtener los documentos del user del paciente
        $query = Documento::query()
            ->where(function ($q) use ($pacienteId, $paciente) {
                $q->where('user_id', $paciente->user_id)
                    ->orWhereHas('historial', function ($h) use ($pacienteId) {
                        $h->where('id_paciente', $pacienteId);
                    });
            });

        // Si es especialista, solo lo que está marcado como "compartido"
        if ($user->hasRole('especialista')) {
            $query->where('visible_para_especialista', 1);
        }

        $documentos = $query
            ->with([
                'historial:id,id_paciente',
                'historial.paciente:id,user_id',
            ])
            ->orderByDesc('created_at')
            ->get();

        if ($documentos->isEmpty()) {
            $respuesta = [
                'message' => 'No hay documentos disponibles para este paciente',
                'documentos' => [],
            ];
            $this->registrarLog($user->id, 'obtener_documentos_paciente', "Sin documentos para paciente ID $pacienteId");
        } else {
            $respuesta = ['documentos' => $documentos];
            $this->registrarLog($user->id, 'obtener_documentos_paciente', "Documentos del paciente ID $pacienteId listados correctamente");
        }

        return response()->json($respuesta, $codigo);
    }




}

