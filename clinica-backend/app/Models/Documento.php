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
 * @property string $nombre Nombre del documento
 * @property string $archivo Ruta del archivo del documento
 * @property string $tipo Tipo de documento (ej. 'pdf', 'imagen')
 * @property int $tamano Tamaño del archivo en bytes
 * @property string $descripcion Descripción del documento
 */
class Documento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documentos';

    protected $fillable = [
        'historial_id',
        'user_id',
        'nombre',
        'archivo',
        'tipo',
        'tamano',
        'descripcion',
    ];

    protected $casts = [
        'tamano' => 'integer',
    ];

    /**
     * Relación con el modelo Historial.
     * Un documento pertenece a un historial médico.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function historial()
    {
        return $this->belongsTo(Historial::class, 'historial_id');
    }

    /**
     * Relación con el modelo User.
     * Un documento pertenece a un usuario propietario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function propietario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el modelo Paciente.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Paciente, Documento>
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'user_id', 'user_id');
    }
}
