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
class CreateDocumentosTable extends Migration
{
    public function up()
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historial_id')->constrained('historiales')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->string('nombre');
            $table->string('archivo'); // ruta o nombre archivo
            $table->string('tipo');
            $table->unsignedBigInteger('tamano')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('documentos');
    }
}
