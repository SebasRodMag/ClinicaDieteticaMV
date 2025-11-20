<?php

namespace Docs\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Gestión Clínica MV API",
 *     version="1.0.0",
 *     description="API para la gestión de usuarios, pacientes, citas e historiales médicos."
 * )
 */

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Operaciones de autenticación de usuarios"
 * )
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
class AuthDocumentacion {}
