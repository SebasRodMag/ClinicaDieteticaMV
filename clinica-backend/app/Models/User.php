<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo User
 * Representa un usuario en la aplicación.
 * @property int $id Identificador único del usuario
 * @property string $nombre Nombre del usuario
 * @property string $apellidos Apellidos del usuario
 * @property string $dni_usuario DNI del usuario
 * @property string $email Correo electrónico del usuario
 * @property \Carbon\Carbon $fecha_nacimiento Fecha de nacimiento del usuario
 * @property string $telefono Teléfono de contacto del usuario
 * @property string $direccion Dirección del usuario
 * @property string $password Contraseña del usuario (encriptada)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'nombre',
        'apellidos',
        'dni_usuario',
        'email',
        'fecha_nacimiento',
        'telefono',
        'direccion',
        'password',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'paciente',
        'especialista',
    ];

    /**
     * Los atributos que deberían ser mutados a tipos de datos nativos.
     * Relación con Paciente (1:1)
     * @var array
     */
    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'user_id');
    }

    /**
     * Los atributos que deberían ser mutados a tipos de datos nativos.
     * Relación con Especialista (1:1)
     * @var array
     */
    public function especialista()
    {
        return $this->hasOne(Especialista::class, 'user_id');
    }
}
