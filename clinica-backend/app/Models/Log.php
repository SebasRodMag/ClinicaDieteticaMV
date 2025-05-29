<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo Log
 * Representa un registro de log en la aplicación.
 * @property int $id Identificador único del log
 * @property int $user_id Identificador del usuario que realizó la acción
 * @property string $accion Descripción de la acción realizada
 * @property string $tabla_afectada Nombre de la tabla afectada por la acción
 * @property int $registro_id Identificador del registro afectado en la tabla
 */
class Log extends Model
{
    use HasFactory;

    protected $table = 'logs';

    protected $fillable = [
        'user_id',
        'accion',
        'tabla_afectada',
        'registro_id',
    ];

    /**
     * Relación con el modelo User.
     * Un log pertenece a un usuario que realizó la acción.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
