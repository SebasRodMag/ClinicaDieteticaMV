<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Historial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'historial';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_paciente',
        'id_especialista',
        'id_cita',
        'fecha',
        'comentarios_paciente',
        'observaciones_especialista',
        'recomendaciones',
        'dieta',
        'lista_compra',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id');
    }

    public function especialista()
    {
        return $this->belongsTo(Especialista::class, 'id_especialista');
    }

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'historial_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}