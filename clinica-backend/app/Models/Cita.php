<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
/**
 * Modelo Cita
 * Representa una cita médica en la aplicación.
 * @property int $id_cita Identificador único de la cita
 * @property int $id_paciente Identificador del paciente asociado a la cita
 * @property int $id_especialista Identificador del especialista asociado a la cita
 */
class Cita extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'citas';
    protected $primaryKey = 'id_cita';

    protected $fillable = [
        'id_paciente',
        'id_especialista',
        'fecha_hora_cita',
        'tipo_cita',
        'estado',
        'es_primera',
        'comentario',
    ];

    protected $casts = [
        'es_primera' => 'boolean',
        'fecha_hora_cita' => 'datetime',
    ];

    /**
     * Relación con el modelo Paciente.
     * Una cita pertenece a un paciente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    /**
     * Relación con el modelo Especialista.
     * Una cita pertenece a un especialista.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function especialista()
    {
        return $this->belongsTo(Especialista::class, 'id_especialista');
    }

    /**
     * Verifica si la cita es telemática.
     *
     * @return bool
     */
	public function esTelematica()
	{
		return $this->tipo_cita === 'telemática';
	}
}