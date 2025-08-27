<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/**
 * Migration para crear la tabla de logs.
 * Esta migración define la estructura de la tabla 'logs' en la base de datos.
 * Incluye campos para el usuario, acción realizada, tabla afectada,
 * ID del registro afectado y timestamps.
 */
class CreateLogsTable extends Migration
{
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion');
            $table->string('tabla_afectada');
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('logs');
    }
}

