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
return new class extends Migration {
    public function up(): void
    {
        Schema::create('especialistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->string('especialidad')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialistas');
    }
};