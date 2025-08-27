<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration para crear la tabla de citas.
 * Esta migración define la estructura de la tabla 'citas' en la base de datos.
 * Incluye campos para el paciente, especialista, fecha y hora de la cita,
 * tipo de cita, estado, si es primera cita, comentario y timestamps.
 */
class CreatePacientesTable extends Migration
{
    public function up()
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('numero_historial')->unique();
            $table->date('fecha_alta')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pacientes');
    }
}
