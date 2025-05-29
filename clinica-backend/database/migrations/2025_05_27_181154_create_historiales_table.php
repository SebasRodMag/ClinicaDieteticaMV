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
class CreateHistorialesTable extends Migration
{
    public function up()
    {
        Schema::create('historiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('id_especialista')->constrained('especialistas')->onDelete('cascade');
            $table->text('comentarios_paciente')->nullable();
            $table->text('observaciones_especialista')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->text('dieta')->nullable();
            $table->text('lista_compra')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('historiales');
    }
}
