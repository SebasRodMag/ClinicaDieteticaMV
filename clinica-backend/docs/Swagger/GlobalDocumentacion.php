<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Gestión Clínica MV API",
 *     version="1.0.0",
 *     description="API para la gestión de usuarios, pacientes, especialistas, citas, historiales médicos y documentos en la plataforma Clínica Dietética MV."
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor base de la API"
 * )
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="Operaciones de autenticación y gestión de sesión."
 * )
 *
 * @OA\Tag(
 *     name="Pacientes",
 *     description="Gestión de pacientes y consulta de pacientes asociados a especialistas."
 * )
 *
 * @OA\Schema(
 *     schema="MensajeError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Ha ocurrido un error."),
 *     description="Formato genérico de respuesta de error."
 * )
 *
 * @OA\Schema(
 *     schema="PacienteAsociado",
 *     type="object",
 *     description="Paciente asociado a un especialista con cita registrada.",
 *     @OA\Property(property="id", type="integer", example=3),
 *     @OA\Property(property="user_id", type="integer", example=12),
 *     @OA\Property(property="numero_historial", type="string", example="AB123456CD"),
 *     @OA\Property(property="nombre", type="string", example="Laura"),
 *     @OA\Property(property="apellidos", type="string", example="Pérez López")
 * )
 *
 * @OA\Schema(
 *     schema="RespuestaPacientesAsociados",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Pacientes obtenidos correctamente"),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PacienteAsociado")
 *     )
 * )
 */
class GlobalDocumentacion
{
    // Esta clase existe solo para agrupar anotaciones globales de Swagger.
}
