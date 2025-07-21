<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Documento;
use Illuminate\Http\JsonResponse;
use App\Models\Historial;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Traits\Loggable;


class DocumentoController extends Controller
{


    use Loggable;
    /**
     * Listar documentos según el rol del usuario autenticado.
     * - Administrador ve todos los documentos.
     * - Especialista ve documentos de pacientes asignados.
     * - Paciente ve solo sus documentos.
     * * @return JsonResponse devuelve una respuesta JSON con los documentos o un mensaje de error si no hay documentos disponibles.
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
     * Controla el acceso según el rol del usuario:
     * - Administrador: acceso total.
     * - Especialista: acceso a documentos de sus pacientes.
     * - Paciente: acceso solo a sus propios documentos.
     *
     * @param int $id ID del documento que se desea ver
     * @return JsonResponse devuelve una respuesta JSON con el documento o un mensaje de error si no se encuentra o no está autorizado.
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
     * Eliminar un documento si el usuario es su propietario o es admin.
     * Controla el acceso según el rol del usuario:
     * - Administrador: acceso total.
     * - Especialista: acceso a documentos de pacientes asignados.
     * - Paciente: acceso solo a sus propios documentos.
     * @param int $id ID del documento a eliminar
     * @return JsonResponse devuelve una respuesta JSON con el estado de la operación.
     * @throws \Exception lanza una excepción si ocurre un error al eliminar el documento.
     * 
     */
    public function eliminarDocumento(int $id): JsonResponse
    {
        $respuesta = [];
        $codigo = 200;

        if (!is_numeric($id)) {
            $this->registrarLog(auth()->id(), 'eliminar_documento_fallido', "ID inválido (no numérico): $id");
            $respuesta = ['message' => 'ID inválido'];
            $codigo = 400;
        }

        $documento = Documento::find($id);

        if (!$documento) {
            $this->registrarLog(auth()->id(), 'eliminar_documento_fallido', "Documento ID $id no encontrado");
            $respuesta = ['message' => 'Documento no encontrado'];
            $codigo = 404;
        } elseif ($documento->user_id !== auth()->id() && !auth()->user()->hasRole('administrador')) {
            $this->registrarLog(auth()->id(), 'eliminar_documento_denegado', "Acceso denegado a documento ID $id");
            $respuesta = ['message' => 'No tienes permiso para eliminar este documento'];
            $codigo = 403;
        } else {
            try {
                Storage::disk('public')->delete($documento->archivo);
                $documento->delete();

                $this->registrarLog(auth()->id(), 'eliminar_documento', "Documento ID $id eliminado");

                $respuesta = ['message' => 'Documento eliminado correctamente'];
            } catch (\Exception $e) {
                $this->registrarLog(auth()->id(), 'eliminar_documento_error', "Error eliminando documento: {$e->getMessage()}");
                $respuesta = ['message' => 'Error al eliminar el documento'];
                $codigo = 500;
            }
        }

        return response()->json($respuesta, $codigo);
    }

    /**
     * Listar los documentos del usuario autenticado (si es paciente) o de sus pacientes (si es especialista).
     * Controla el acceso según el rol del usuario:
     * - Paciente: ve solo sus documentos.
     * - Especialista: ve documentos de pacientes asignados.
     * * @return JsonResponse devuelve una respuesta JSON con los documentos del usuario o un mensaje de error si no hay documentos disponibles.
     * 
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
     * Esta función permite a un usuario subir un documento asociado a un historial médico.
     * Se valida que el archivo sea de tipo PDF, JPG, JPEG o PNG y que no supere los 5MB.
     * Controla el acceso según el rol del usuario:
     * - Administrador: acceso total.
     * - Especialista: acceso a documentos de pacientes asignados.
     * - Paciente: acceso solo a sus propios historiales.
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
     * Controla el acceso según el rol del usuario:
     * - Administrador: acceso total.
     * - Especialista: acceso a documentos de pacientes asignados.
     * - Paciente: acceso solo a sus propios documentos.
     * @param int $id ID del documento a descargar
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse devuelve una respuesta JSON con el estado de la operación o un archivo descargable.
     * @throws \Exception lanza una excepción si ocurre un error al descargar el documento.
     */
    public function descargarDocumento(int $id): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $documento = Documento::find($id);

        if (!$documento) {
            $this->registrarLog(auth()->id(), 'descargar_documento_fallido', "Documento ID $id no encontrado");
            return response()->json(['message' => 'Documento no encontrado'], 404);
        }

        $usuario = auth()->user();
        $esPropietario = $documento->user_id === $usuario->id;
        $esEspecialistaRelacionado = false;

        if ($usuario->hasRole('especialista')) {
            $especialista = $usuario->especialista;
            if ($especialista) {
                $esEspecialistaRelacionado = $especialista->citas()
                    ->where('paciente_id', $documento->user_id)
                    ->whereIn('estado', ['pendiente', 'confirmada', 'finalizada'])
                    ->exists();
            }
        }

        if (!$esPropietario && !$esEspecialistaRelacionado) {
            $this->registrarLog($usuario->id, 'descargar_documento_denegado', "Acceso denegado a documento ID $id");
            return response()->json(['message' => 'No tienes permiso para descargar este documento'], 403);
        }

        if (!Storage::disk('public')->exists($documento->archivo)) {
            $this->registrarLog($usuario->id, 'descargar_documento_fallido', "Archivo físico no encontrado para documento ID $id");
            return response()->json(['message' => 'Archivo no encontrado en el servidor'], 404);
        }

        $this->registrarLog($usuario->id, 'descargar_documento', "Descarga del documento ID $id");

        return Storage::disk('public')->download($documento->archivo, $documento->nombre);
    }


}

