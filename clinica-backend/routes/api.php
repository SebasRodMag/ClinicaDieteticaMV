<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EspecialistaController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\HistorialController;
use Spatie\Permission\Middleware\RoleMiddleware;

// Rutas públicas (sin autenticación)
Route::post('/register', [AuthController::class, 'registrar']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas - usuarios autenticados
Route::middleware('auth:sanctum')->group(function () {
    
    // Info usuario logueado y logout
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Usuarios CRUD - solo admin puede gestionar usuarios
    Route::middleware('role:administrador')->group(function () {
        Route::get('usuarios', [UserController::class, 'listarTodos']);
        Route::get('especialistas', [EspecialistaController::class, 'listarEspecialistas']);
        Route::get('pacientes', [PacienteController::class, 'listarPacientes']);
        Route::get('pacienteslistado', [PacienteController::class, 'pacientesConEspecialista']);
        Route::get('usuarios/{id}', [UserController::class, 'verUsuario']);
        Route::post('usuarios', [UserController::class, 'crearUsuario']);
        Route::put('usuarios/{id}', [UserController::class, 'actualizarUsuario']);
        Route::delete('usuarios/{id}', [UserController::class, 'eliminarUsuario']);
        Route::get('especialistas/{id}', [EspecialistaController::class, 'verEspecialista']);
        Route::post('especialistas', [EspecialistaController::class, 'crearEspecialista']);
        Route::put('especialistas/{id}', [EspecialistaController::class, 'actualizarEspecialista']);
        Route::delete('especialistas/{id}', [EspecialistaController::class, 'eliminarEspecialista']);
        Route::get('pacientes/{id}', [PacienteController::class, 'verPaciente']);
        Route::post('pacientes', [PacienteController::class, 'crearPaciente']);
        Route::put('pacientes/{id}', [PacienteController::class, 'actualizarPaciente']);
        Route::delete('pacientes/{id}', [PacienteController::class, 'eliminarPaciente']);
        Route::get('pacientestodos', [PacienteController::class, 'getFullPacientes']);
        Route::get('citas', [CitaController::class, 'listarCitas']);
        Route::get('citas/{id}', [CitaController::class, 'verCita']);
        Route::post('citas', [CitaController::class, 'crearCita']);
        Route::put('citas/{id}', [CitaController::class, 'actualizarCita']);
        Route::delete('citas/{id}', [CitaController::class, 'eliminarCita']);
        

    });

    // Gestión de citas - especialistas y pacientes (permiso personalizado en controlador)
    

    // Documentos (acceso controlado internamente por roles)
    Route::get('/documentos', [DocumentoController::class, 'listarDocumentos']);
    Route::get('/documentos/{id}', [DocumentoController::class, 'verDocumento']);
    Route::post('/documentos', [DocumentoController::class, 'subirDocumento']);
    Route::delete('/documentos/{id}', [DocumentoController::class, 'eliminarDocumento']);
    Route::get('/documentos/{id}/descargar', [DocumentoController::class, 'descargarDocumento']);
    Route::get('/mis-documentos', [DocumentoController::class, 'listarMisDocumentos']);

    // Historial médico
    Route::get('/historial/{id}', [HistorialController::class, 'verHistorial']);
    Route::post('/historial/{id}/entrada', [HistorialController::class, 'agregarEntrada']);
    Route::put('/historial/{id}', [HistorialController::class, 'actualizarHistorial']);
    
    // Ruta ejemplo para área admin con middleware de rol
    Route::get('/admin', fn() => response()->json(['message' => 'Área de admin']))
        ->middleware('role:adminstrador');
});
