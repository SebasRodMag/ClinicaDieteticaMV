<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/**
 * Migration para crear la tabla de usuarios.
 * Esta migración define la estructura de la tabla 'users' en la base de datos.
 * Incluye campos para nombre, apellidos, DNI, email, dirección, fecha de nacimiento,
 * teléfono y contraseña, así como timestamps para seguimiento de creación y actualización.
 */
class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('dni_usuario')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
			$table->string('direccion')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
