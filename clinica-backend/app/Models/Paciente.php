<?php

namespace App\Models;

use App\Models\Cita;
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

    protected $table = 'pacientes';

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
    /* public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    } */

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
        return $this->hasMany(Cita::class, 'id_paciente');
    }

    public function ultimaCita()
    {
        return $this->hasOne(Cita::class, 'id_paciente', 'id')
                    ->latestOfMany('fecha_hora_cita');
    }

    public function especialistas() {
        // Relación indirecta a especialistas a través de citas
        return $this->hasManyThrough(
            Especialista::class,
            Cita::class,
            'id_paciente',
            'user_id',
            'user_id',
            'id_especialista'
        );
    }

    public function listarPacientes()
    {
        $pacientes = Paciente::with(['usuario', 'especialista.usuario'])->get();
        return response()->json($pacientes);
    }
}
