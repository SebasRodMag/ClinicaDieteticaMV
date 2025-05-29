<?php

namespace App\Traits;

use App\Models\Log;

trait Loggable
{
    /**
     * Registrar una acción en los logs.
     *
     * @param int|null $userId ID del usuario que realiza la acción (si null, intenta usar el usuario autenticado)
     * @param string $accion Acción realizada
     * @param string|null $tablaAfectada Tabla afectada por la acción
     * @param int|null $registroId ID del registro afectado
     * @return void No devuelve nada, solo registra el log.
     */
    public function registrarLog(?int $userId, string $accion, ?string $tablaAfectada = null, ?int $registroId = null): void
    {
        $userId = $userId ?? auth()->id();

        if ($userId === null || $tablaAfectada === null) {
            return;
        }

        Log::create([
            'user_id' => $userId,
            'accion' => $accion,
            'tabla_afectada' => $tablaAfectada,
            'registro_id' => $registroId,
        ]);
    }
}