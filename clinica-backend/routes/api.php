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

/**
 * Rutas públicas (sin autenticación) para registro, login y obtención de configuraciones
 * Estas rutas no requieren token de acceso. Se usan para:
 * - Registrar nuevos usuarios
 * - Autenticar usuarios existentes
 * - Obtener el color del tema de la aplicación
 */

Route::post('/register', [UserController::class, 'crearUsuario']);
//Existe un método register en AuthController, pero utilizo el implementado en UserController porque contempla más casuísticas
Route::post('/login', [AuthController::class, 'login']);
Route::get('/color-tema', [ConfiguracionController::class, 'obtenerColorTema']);
// Devuelve el color de tema actual de la aplicación desde la configuración general

/**
 * Rutas protegidas - requieren autentificación con Sanctum
 * Todas las rutas dentro de este grupo requieren que el usuario esté autenticado.
 * Además, algunas rutas tienen restricciones adicionales basadas en el rol del usuario:
 * - Administrador: Acceso completo para gestionar usuarios, especialistas, pacientes, citas y configuraciones.
 * - Especialista: Acceso a funcionalidades relacionadas con su perfil, pacientes e historial médico.
 * - Paciente: Acceso a funcionalidades relacionadas con su perfil, citas e historial médico.
 * - paciente | especialista | administrador (zona “compartida”)
 */

Route::middleware('auth:sanctum')->group(function () {

    /**
     * Info usuario logueado y logout
     */ 
    Route::get('/me', [AuthController::class, 'me']);// Devuelve datos del usuario autenticado
    Route::post('/logout', [AuthController::class, 'logout']);// Revoca el token actual

    /*
    * Rutas exclusivas para ADMINISTRADOR
    *
    * Gestión global del sistema:
    * - Logs
    * - Resumen dashboard
    * - Gestión completa de usuarios (CRUD + cambio/gestión de rol)
    * - Gestión completa de pacientes y especialistas
    * - Gestión global de citas (listado, detalles, eliminación)
    * - Configuraciones generales
    */

    Route::middleware('role:administrador')->group(function () {

        // Logs y resumen para dashboard del administrador
        Route::get('logs', [LogController::class, 'listarLogs']);
        Route::get('admin/resumen-dashboard', [ConfiguracionController::class, 'resumen']);

        // Gestión de usuarios (CRUD + cambio de rol)
        Route::get('usuarios', [UserController::class, 'listarTodos']);
        Route::get('usuarios/{id}', [UserController::class, 'verUsuario']);
        Route::post('usuarios', [UserController::class, 'crearUsuario']);
        Route::post('usuarios-rol-usuario', [UserController::class, 'crearUsuarioRolUsuario']); 
        // Crea usuario directamente con rol "usuario" , ya que el administrador puede crear un usuario y asignarle el rol que necesite

        Route::put('usuarios/{id}', [UserController::class, 'actualizarUsuario']);
        Route::delete('usuarios/{id}', [UserController::class, 'borrarUsuario']);

        Route::put('usuariosbaja/{id}', [UserController::class, 'cambiarRol']); 
        // Marca baja o cambia rol del usuario (p.ej. de paciente/especialista a usuario)

        // Listado de usuarios sin rol de especialista ni paciente (para asignarlos luego)
        Route::get('usuarios/listar/usuarios', [UserController::class, 'getUsuariosSinRolEspecialistaNiPaciente']);

        // Gestión de especialistas (CRUD)
        Route::get('/especialistasfull', [EspecialistaController::class, 'listarEspecialistasFull']); 
        // Devuelve especialistas con información ampliada para el administrador

        Route::post('especialistas', [EspecialistaController::class, 'nuevoEspecialista']);
        Route::put('especialistas/{id}', [EspecialistaController::class, 'actualizarEspecialista']);
        Route::delete('especialistas/{id}', [EspecialistaController::class, 'eliminarEspecialista']);

        // Gestión de pacientes (CRUD)
        Route::get('pacienteslistado', [PacienteController::class, 'pacientesConEspecialista']); 
        // Listado de pacientes con su especialista asignado

        Route::get('pacientes/{id}/ver', [PacienteController::class, 'verPaciente']);
        Route::post('pacientes', [PacienteController::class, 'crearPaciente']);
        Route::put('pacientes/{id}', [PacienteController::class, 'actualizarPaciente']);
        Route::delete('pacientes/{id}', [PacienteController::class, 'eliminarPaciente']);

        Route::get('pacientestodos', [PacienteController::class, 'getFullPacientes']); 
        // Listado completo de pacientes (vista de administración)

        // Gestión global de citas (ADMIN)
        Route::get('citas', [CitaController::class, 'listarCitas']);      // Todas las citas del sistema
        Route::get('citas/{id}', [CitaController::class, 'verCita']);     // Ver detalles de una cita concreta
        Route::delete('citas/{id}', [CitaController::class, 'eliminarCita']); // Eliminar cita (uso administrativo)

        // Consultar horas disponibles de un especialista concreto
        Route::get('especialista/horas-disponibles/{fecha}', [CitaController::class, 'horasDisponiblesEspecialista']);
        // Misma lógica que la del especialista, pero accesible al admin

        // Configuración general (modificación por clave)
        Route::put('cambiarConfiguraciones/{clave}', [ConfiguracionController::class, 'actualizarPorClave']);
        // Permite cambiar una configuración concreta (clave/valor)

    });

    /*
    * Rutas compartidas: paciente, especialista y administrador
    *
    * Son rutas que usan principalmente las vistas de citas, selección de
    * especialistas, configuración general visible, documentos compartidos, etc.
    */

    Route::middleware('role:paciente|especialista|administrador')->group(function () {

        // Citas: horas disponibles y creación
        Route::get('horas-disponibles/{idEspecialista}/{fecha}', [CitaController::class, 'horasDisponiblesPorEspecialista']);
        Route::get('especialistas/{id}/horas-disponibles', [CitaController::class, 'horasDisponibles']);
        Route::post('citas', [CitaController::class, 'nuevaCita']); // Creación de nueva cita (flujo genérico)

        // Listados básicos de pacientes y especialistas (para selector en formularios, etc.)
        Route::get('pacientes', [PacienteController::class, 'listarPacientes']);
        Route::get('pacientespornombre', [PacienteController::class, 'listarPacientesPorNombre']);
        Route::get('especialistapornombre', [EspecialistaController::class, 'listarEspecialistasPorNombre']);
        Route::get('especialidades', [EspecialistaController::class, 'listarEspecialidades']);
        Route::get('especialistas-por-especialidad', [EspecialistaController::class, 'listarEspecialistasPorEspecialidad']);
        Route::get('especialistas/{id}', [EspecialistaController::class, 'verEspecialista']);

        // Configuración general “visible” para el frontal
        Route::get('configuracion-general', [CitaController::class, 'configuracion']);
        Route::get('obtenerConfiguraciones', [ConfiguracionController::class, 'obtenerConfiguracionesConMensaje']);

        // Citas: actualización y estados
        Route::put('actualizar-citas/{id}', [CitaController::class, 'actualizarCita']); 
        // Actualiza datos de la cita (fecha, hora, etc.)

        Route::get('estados/estados-cita', [CitaController::class, 'tiposEstadoCita']); 
        // Devuelve tipos de estado posibles para las citas

        // Historiales disponibles para usuarios con rol adecuado
        Route::get('historiales/pacientes/', [HistorialController::class, 'listarHistorialesPacientes']);

        // Citas del usuario autenticado (paciente o especialista según contexto)
        Route::get('listar-citas-paciente', [CitaController::class, 'listarMisCitas']); 
        // En la práctica se usa para el paciente y para el especialista (según el rol actual)

        // Sala segura para videoconferencia (Jitsi)
        Route::get('obtener-sala/{id}', [CitaController::class, 'obtenerSalaSegura']);
        Route::get('citas/{id}/sala-segura', [CitaController::class, 'obtenerSalaSegura']); 
        // Alias/duplicado semántico para obtener la sala segura partiendo de la cita

        // Documentos: subida, listados, detalle, descarga y eliminación
        Route::post('documentos', [DocumentoController::class, 'subirDocumento']);
        Route::get('documentos', [DocumentoController::class, 'listarDocumentos']);           // Listado general (según rol)
        Route::get('mis-documentos', [DocumentoController::class, 'listarMisDocumentos']);    // Documentos propios o de pacientes (según rol)
        Route::get('documentos/{id}', [DocumentoController::class, 'verDocumento']);          // Detalle documento con control de acceso
        Route::get('documentos/{id}/descargar', [DocumentoController::class, 'descargarDocumento']); // Descarga con verificación de permisos
        Route::get('/pacientes/{paciente}/documentos', [DocumentoController::class, 'obtenerDocumentosPorPaciente']); 
        // Documentos filtrados por paciente (vista especialista/admin)

        Route::delete('documentos/{id}', [DocumentoController::class, 'eliminarDocumento']);  // Eliminación según permisos

        // Gestión rápida de paciente desde rutas compartidas
        Route::post('nuevo-paciente', [PacienteController::class, 'nuevoPaciente']); 
        // Crea un nuevo paciente a partir de un usuario sin rol (flujo usado en el modal del admin)

        // Acciones específicas de cita (cancelar / cambiar estado)
        Route::patch('citas/{id}/cancelar', [CitaController::class, 'cancelarCita']);        // Cancelación desde paciente o especialista (según reglas)
        Route::patch('citas/{id}/cambiar-estado', [CitaController::class, 'cambiarEstadoCita']); 
        // Cambio de estado genérico (p.ej. realizada, cancelada, ausente)

    });

    /*
    * Rutas específicas para PACIENTE
    *
    * Funcionalidad pensada para la vista de paciente:
    * - Crear cita asociada explícitamente al paciente
    * - Ver y actualizar su propio perfil
    * - Cambiar su contraseña
    * - Ver sus historiales
    */

    Route::middleware('role:paciente')->group(function () {

        // Creación de cita para un paciente concreto (flujo alternativo a POST /citas)
        Route::post('pacientes/{id}/citas', [CitaController::class, 'crearNuevaCita']);

        // Historiales del paciente autenticado
        Route::get('mis-historiales', [HistorialController::class, 'historialesPorPaciente']);

        // Listado de especialistas filtrados por especialidad para la vista de paciente
        Route::get('especialistas', [EspecialistaController::class, 'listarEspecialistasPorEspecialidad']);

        // Perfil del paciente (ver y actualizar sus propios datos)
        Route::get('pacientes/{id}', [PacienteController::class, 'verPaciente']);
        Route::put('pacientes/{id}', [PacienteController::class, 'actualizarPaciente']);

        // Cambio de contraseña del paciente
        Route::put('pacientes/{id}/cambiar-password', [PacienteController::class, 'cambiarPassword']);
    });

    /*
    * Rutas específicas para ESPECIALISTA
    *
    * Funcionalidad pensada para la vista del especialista:
    * - Consultar sus propias horas disponibles
    * - Gestionar historiales (crear, actualizar, eliminar)
    * - Listar pacientes asignados al especialista
    * - Ver su propio perfil
    * - Listar todas sus citas (vista especialista)
    */

    Route::middleware('role:especialista')->group(function () {

        // Horas disponibles del especialista autenticado
        Route::get('especialista/horas-disponibles/{fecha}', [CitaController::class, 'horasDisponiblesEspecialista']);

        // Gestión de historiales médicos (solo especialista)
        Route::get('historial-paciente/', [HistorialController::class, 'listarHistoriales']);
        Route::post('historial/', [HistorialController::class, 'nuevaEntrada']);
        Route::put('historial/{id}', [HistorialController::class, 'actualizarEntrada']);
        Route::delete('historial/{id}', [HistorialController::class, 'eliminarEntrada']);

        // Pacientes asignados al especialista
        Route::get('paciente-por-especialista', [PacienteController::class, 'listarPacientesDelEspecialista']);

        // Perfil del especialista
        Route::get('perfilespecialista', [EspecialistaController::class, 'perfilEspecialista']);

        // Listado de citas del especialista (todas sus citas con pacientes)
        Route::get('pacientes/citas/todas', [CitaController::class, 'listarMisCitas']);
    });

});