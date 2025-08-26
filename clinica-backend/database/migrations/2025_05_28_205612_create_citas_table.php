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
class CreateCitasTable extends Migration
{
    public function up()
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id('id_cita');
            $table->foreignId('id_paciente')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('id_especialista')->constrained('especialistas')->onDelete('cascade');
            $table->dateTime('fecha_hora_cita');
            $table->enum('tipo_cita', ['presencial', 'telemática'])->default('presencial');
            $table->enum('estado', ['pendiente','realizada','cancelada','finalizada','ausente','reasignada'])->default('pendiente');
            $table->boolean('es_primera')->default(false);
            $table->text('comentario')->nullable();
            $table->string('nombre_sala')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('citas');
    }
}

