<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo Especialista
 * Representa a un especialista médico en la aplicación.
 * @property int $id Identificador único del especialista
 * @property int $user_id Identificador del usuario asociado al especialista
 * @property string $especialidad Especialidad del especialista
 */
class Historial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'historiales';

    protected $fillable = [
        'id_paciente',
        'id_especialista',
        'comentarios_paciente',
        'observaciones_especialista',
        'recomendaciones',
        'dieta',
        'lista_compra',
    ];

    /**
     * Relación con el modelo User.
     * Un historial pertenece a un paciente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paciente()
    {
        return $this->belongsTo(User::class, 'id_paciente');
    }

    /**
     * Relación con el modelo User.
     * Un historial pertenece a un especialista.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function especialista()
    {
        return $this->belongsTo(User::class, 'id_especialista');
    }

    /**
     * Relación con el modelo Documento.
     * Un historial puede tener muchos documentos asociados.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'historial_id');
    }
}
