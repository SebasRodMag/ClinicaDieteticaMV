<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EspecialistaController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\LogController;
use Spatie\Permission\Middleware\RoleMiddleware;

// Rutas públicas (sin autenticación)
Route::post('/register', [AuthController::class, 'registrar']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/color-tema', [ConfiguracionController::class, 'obtenerColorTema']);

// Rutas protegidas - usuarios autenticados
Route::middleware('auth:sanctum')->group(function () {

    // Info usuario logueado y logout
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Usuarios CRUD - solo admin puede gestionar usuarios
    Route::middleware('role:administrador')->group(function () {
        Route::get('logs', [LogController::class, 'listarLogs']);
        Route::get('admin/resumen-dashboard', [ConfiguracionController::class, 'resumen']);
        Route::get('usuarios', [UserController::class, 'listarTodos']);
        Route::put('usuariosbaja/{id}', [UserController::class, 'cambiarRol']);

        Route::get('pacienteslistado', [PacienteController::class, 'pacientesConEspecialista']);
        Route::get('usuarios/{id}', [UserController::class, 'verUsuario']);
        Route::post('usuarios', [UserController::class, 'crearUsuario']);
        Route::put('usuarios/{id}', [UserController::class, 'actualizarUsuario']);
        Route::delete('usuarios/{id}', [UserController::class, 'borrarUsuario']);
        Route::get('usuarios/listar/usuarios', [UserController::class, 'getUsuariosSinRolEspecialistaNiPaciente']);
        Route::get('/especialistasfull', [EspecialistaController::class, 'listarEspecialistasFull']);
        
        Route::post('especialistas', [EspecialistaController::class, 'nuevoEspecialista']);
        Route::put('especialistas/{id}', [EspecialistaController::class, 'actualizarEspecialista']);
        Route::delete('especialistas/{id}', [EspecialistaController::class, 'eliminarEspecialista']);
        Route::get('pacientes/{id}/ver', [PacienteController::class, 'verPaciente']);
        Route::post('pacientes', [PacienteController::class, 'crearPaciente']);
        Route::put('pacientes/{id}', [PacienteController::class, 'actualizarPaciente']);
        Route::delete('pacientes/{id}', [PacienteController::class, 'eliminarPaciente']);
        Route::get('pacientestodos', [PacienteController::class, 'getFullPacientes']);
        Route::get('citas', [CitaController::class, 'listarCitas']);
        Route::get('citas/{id}', [CitaController::class, 'verCita']);

        Route::delete('citas/{id}', [CitaController::class, 'eliminarCita']);

        Route::put('cambiarConfiguraciones/{clave}', [ConfiguracionController::class, 'actualizarPorClave']);


    });

    Route::middleware('role:paciente|especialista|administrador')->group(function () {
        Route::get('horas-disponibles/{fecha}', [CitaController::class, 'horasDisponibles']);
        Route::post('citas', [CitaController::class, 'nuevaCita']);
        Route::get('pacientes', [PacienteController::class, 'listarPacientes']);
        Route::get('pacientespornombre', [PacienteController::class, 'listarPacientesPorNombre']);
        Route::get('especialistapornombre', [EspecialistaController::class, 'listarEspecialistasPorNombre']);
        Route::get('especialistas/{id}/horas-disponibles', [CitaController::class, 'horasDisponibles']);
        Route::get('configuracion-general', [CitaController::class, 'configuracion']);
        Route::get('especialidades', [EspecialistaController::class, 'listarEspecialidades']);
        Route::get('especialistas-por-especialidad', [EspecialistaController::class, 'listarEspecialistasPorEspecialidad']);
        Route::get('obtenerConfiguraciones', [ConfiguracionController::class, 'obtenerConfiguracionesConMensaje']);
        Route::put('actualizar-citas/{id}', [CitaController::class, 'actualizarCita']);
        Route::get('estados/estados-cita', [CitaController::class, 'tiposEstadoCita']);
        Route::get('historiales/pacientes/', [HistorialController::class, 'listarHistorialesPacientes']);
        Route::get('especialistas/{id}', [EspecialistaController::class, 'verEspecialista']);

    });
    /**
     * 
     * Rutas para la vista de Paciente
     */

    Route::middleware('role:paciente')->group(function () {

        Route::post('pacientes/{id}/citas', [CitaController::class, 'crearNuevaCita']);
        Route::patch('citas/{id}/cancelar', [CitaController::class, 'cancelarCita']);
        Route::get('mis-historiales', [HistorialController::class, 'historialesPorPaciente']);
        Route::get('especialistas', [EspecialistaController::class, 'listarEspecialistasPorEspecialidad']);
        Route::get('pacientes/{id}', [PacienteController::class, 'verPaciente']);
        Route::put('pacientes/{id}', [PacienteController::class, 'actualizarPaciente']);
        Route::put('pacientes/{id}/cambiar-password', [PacienteController::class, 'cambiarPassword']);
    });


    /**
     * Rutas para la vista de Especialista
     */
    Route::middleware('role:especialista')->group(function () {
        Route::get('historial-paciente/', [HistorialController::class, 'listarHistoriales']);
        Route::post('historial/', [HistorialController::class,'nuevaEntrada']);
        //Route::get('mis-historiales', [HistorialController::class, 'historialesPorPaciente']); ya esta aplicada unicamente para pacientes
        Route::put('historial/{id}', [HistorialController::class, 'actualizarEntrada']);
        Route::delete('historial/{id}', [HistorialController::class, 'eliminarEntrada']);
        Route::get('paciente-por-especialista', [PacienteController::class,'listarPacientesDelEspecialista']);

        Route::get('pacientes/citas/todas', [CitaController::class, 'listarMisCitas']);
        Route::get('/documentos', [DocumentoController::class, 'listarDocumentos']);
        Route::get('/documentos/{id}', [DocumentoController::class, 'verDocumento']);
        Route::post('/documentos', [DocumentoController::class, 'subirDocumento']);
        Route::delete('/documentos/{id}', [DocumentoController::class, 'eliminarDocumento']);
        Route::get('/documentos/{id}/descargar', [DocumentoController::class, 'descargarDocumento']);
        Route::get('/mis-documentos', [DocumentoController::class, 'listarMisDocumentos']);

    });



});
