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
class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = ['user'];

    protected $fillable = [
        'user_id',
        'numero_historial',
        'fecha_alta',
        'fecha_baja',
    ];

    protected $dates = ['fecha_alta', 'fecha_baja'];

    /**
     * Relación con el modelo User.
     * Un paciente pertenece a un usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el modelo Cita.
     * Un paciente puede tener muchas citas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function citas()
    {
        return $this->hasMany(Cita::class, 'user_id');
    }
}
