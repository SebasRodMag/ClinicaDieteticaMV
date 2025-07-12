<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistorialTable extends Migration
{
    public function up()
    {
        Schema::create('historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('id_especialista');
            $table->unsignedBigInteger('id_cita')->nullable();
            $table->date('fecha')->nullable();
            $table->text('comentarios_paciente')->nullable();
            $table->text('observaciones_especialista')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->text('dieta')->nullable();
            $table->text('lista_compra')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_paciente')->references('id')->on('pacientes')->onDelete('cascade');
            $table->foreign('id_especialista')->references('id')->on('especialistas')->onDelete('cascade');
            $table->foreign('id_cita')->references('id_cita')->on('citas')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('historial');
    }
}
