<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo Documento
 * Representa un documento asociado a un historial médico.
 * @property int $id Identificador único del documento
 * @property int $historial_id Identificador del historial médico asociado
 * @property int $user_id Identificador del usuario propietario del documento
 */
class Especialista extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'especialistas';

    protected $hidden = ['user'];

    protected $fillable = [
        'user_id',
        'especialidad',
    ];

    /**
     * Relación con el modelo User.
     * Un especialista pertenece a un usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el modelo Cita.
     * Un especialista puede tener muchas citas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function citas()
    {
        return $this->hasMany(Cita::class, 'id_especialista');
    }
}
