<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Cita;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo Documento
 * Representa un documento asociado a un historial médico.
 * @property int $id Identificador único del documento
 * @property int $historial_id Identificador del historial médico asociado
 * @property int $user_id Identificador del usuario propietario del documento
 * @property string $especialidad Especialidad médica asociada al especialista
 * @property \App\Models\User $user
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Cita[] $citas
 */
class Especialista extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'especialistas';

    //protected $hidden = ['user'];

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
