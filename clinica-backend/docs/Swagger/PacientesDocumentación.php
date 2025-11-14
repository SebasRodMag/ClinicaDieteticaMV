<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/paciente-por-especialista",
 *     tags={"Pacientes"},
 *     summary="Listar pacientes asociados al especialista autenticado",
 *     description="Devuelve los pacientes activos que tienen al menos una cita con el especialista autenticado. Requiere autenticación con token (Sanctum) y rol de especialista.",
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Pacientes obtenidos correctamente.",
 *         @OA\JsonContent(ref="#/components/schemas/RespuestaPacientesAsociados")
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="No autorizado como especialista.",
 *         @OA\JsonContent(ref="#/components/schemas/MensajeError")
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error al obtener los pacientes del especialista.",
 *         @OA\JsonContent(ref="#/components/schemas/MensajeError")
 *     )
 * )
 */
class PacienteDocumentacion
{
    // Esta clase solo contiene las anotaciones para los endpoints de pacientes.
}
