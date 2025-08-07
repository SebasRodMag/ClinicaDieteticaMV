import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Paciente } from '../../models/paciente.model';
import { Usuario } from '../../models/usuario.model';
import { EspecialistaNombre } from '../../models/especialistaPorNombre.modelo';
import { PacienteNombre } from '../../models/pacientePorNombre.model';
import { Especialista } from '../../models/especialista.model';
import { EspecialistaList } from '../../models/especialistaList.model';
import { UsuarioDisponible } from '../../models/usuarioDisponible.model';
import { map } from 'rxjs/operators';
import { CitaPorPaciente } from '../../models/citasPorPaciente.model';
import { CitaPorEspecialista } from '../../models/citasPorEspecialista.model';
import { CitaListado } from '../../models/listarCitas.model';
import { CitaActualizar } from '../../models/citaActualizar.model';
import { Log } from '../../models/log.model';
import { urlApiServicio } from '../../components/utilidades/variable-entorno';
import { Historial } from '../../models/historial.model';
import { AuthService } from '../Auth-Service/Auth.service';

@Injectable({
    providedIn: 'root'
})
export class UserService {
    private apiUrl = urlApiServicio.apiUrl;

    constructor(private http: HttpClient, private authService: AuthService) { }

    logout(): Observable<any> {
        return this.http.post(`${this.apiUrl}/logout`, null);
    }

    getUsuarios(): Observable<{ data: Usuario[] }> {
        return this.http.get<{ data: Usuario[] }>(`${this.apiUrl}/usuarios`);
    }

    /**
 * Obtener el usuario autenticado.
 * @returns Observable con los datos del usuario autenticado.
 */
    getMe(): Observable<Usuario> {
        return this.http.get<{ user: Usuario }>(`${this.apiUrl}/me`)
            .pipe(
                map(response => response.user)
            );
    }


    getUsuario(id: number): Observable<Usuario> {
        return this.http.get<Usuario>(`${this.apiUrl}/usuarios/${id}`);
    }


    updateRolUsuario(id: number): Observable<any> {
        return this.http.put(`${this.apiUrl}/usuariosbaja/${id}`, null);
    }


    eliminarUsuario(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/usuarios/${id}`);
    }

    pacientesConEspecialista(): Observable<Paciente[]> {
        return this.http.get<Paciente[]>(`${this.apiUrl}/pacienteslistado`);
    }

    actualizarPaciente(paciente: Paciente) {
        return this.http.put<Paciente>(`${this.apiUrl}/pacientes/${paciente.id}`, paciente);
    }

    crearPaciente(paciente: Partial<Paciente>): Observable<Paciente> {
        return this.http.post<any>(`${this.apiUrl}/nuevo-paciente`, paciente);
    }

    /**
     * Listamos los pacientes registrados en la clínica.
     * Devuelve un json con un array de pacientes con la siguiente estructura:
     * {
     *    "pacientes": [
     *        {
     *            "id": 1,
     *            "user_id": 103,
     *            "numero_historial": "KR478042TN",
     *            "fecha_alta": "2024-06-01 07:20:28",
     *            "fecha_baja": null,
     *            "created_at": "2025-06-19T20:00:18.000000Z",
     *            "updated_at": "2025-06-19T20:00:18.000000Z",
     *            "deleted_at": null
     *        },
     *    ]
     *}
     * @returns Observable con un array de pacientes.
     */
    listarPacientes(): Observable<{ pacientes: Paciente[] }> {
        return this.http.get<{ pacientes: Paciente[] }>(`${this.apiUrl}/pacientes`);
    }

    crearUsuario(usuario: Usuario): Observable<Usuario> {
        return this.http.post<Usuario>(`${this.apiUrl}/usuarios`, usuario);
    }

    actualizarUsuario(usuario: Usuario): Observable<Usuario> {
        return this.http.put<Usuario>(`${this.apiUrl}/usuarios/${usuario.id}`, usuario);
    }

    /**
     * actualizarCita() actualiza los datos de una cita existente.
     * @param cita Objeto con los datos de la cita a actualizar.
     */
    actualizarCita(cita: CitaActualizar): Observable<any> {
        return this.http.put(`${this.apiUrl}/actualizar-citas/${cita.id_cita}`, cita);
    }
    /**
     * Método getListarEspecialistas() obtiene la lista de pacientes con sus datos completos.
     * Devuelve un json con un array de pacientes con la siguiente estructura:
     * [
     *   {
     *      "id_especialista": 1,
     *      "user_id": 1103,
     *      "nombre_apellidos": "Nombre y Apellidos concatenados",
     *      "email": "especialista1@correo.com",
     *      "telefono": "664590160",
     *      "especialidad": "Endocrinología",
     *      "fecha_alta": "2025-06-19"
     *   },
     * [
     * @returns Observable con la lista de pacientes.
     */
    getListarEspecialistas(): Observable<EspecialistaList[]> {
        return this.http.get<EspecialistaList[]>(`${this.apiUrl}/especialistasfull`);
    }

    crearEspecialista(data: { user_id: number, especialidad: string }): Observable<any> {
        return this.http.post(`${this.apiUrl}/especialistas`, data);
    }

    /**
     * getUsuariosSinRolEspecialistaNiPaciente() obtiene un array de usuarios que no son ni especialistas ni pacientes.
     * Devuelve un json con un array de usuarios con la siguiente estructura:
     * {
     *   "data": [
     *      {
     *          "id": 1,
     *          "nombre_apellidos": "Nombre y Apellidos concatenados"
     *      },
     *      ...
     *   ]
     * }
     * @returns Observable con un array de usuarios disponibles.
     */
    getUsuariosSinRolEspecialistaNiPaciente(): Observable<{ data: UsuarioDisponible[] }> {
        return this.http.get<{ data: UsuarioDisponible[] }>(`${this.apiUrl}/usuarios/listar/usuarios`);
    }

    /**
     * obtenerTodasLasCitas() obtiene un array de citas con sus datos completos e integrantes.
     * Devuelve un json con un array de citas con la siguiente estructura:
     * {
      * "citas": [
     *      {
     *          "id_cita": 1,
     *          "id_paciente": 321,
     *          "id_especialista": 11,
     *          "fecha": "2025-06-23",
     *          "hora": "14:00",
     *          "tipo_cita": "presencial",
     *          "estado": "pendiente",
     *          "nombre_paciente": "Omar Dueñas",
     *          "nombre_especialista": "Jorge Pelayo",
     *          "especialidad": "Endocrinología"
     *          "comentario": "Cita de control"},
     *  ]
     * @returns Observable con un array de citas con sus datos completos he integrantes.
     */
    obtenerTodasLasCitas(): Observable<{ citas: CitaListado[] }> {
        return this.http.get<{ citas: CitaListado[] }>(`${this.apiUrl}/citas`);
    }

    getTiposEstadoCita(): Observable<{ success: boolean; tipos_estado: string[] }> {
        return this.http.get<{ success: boolean; tipos_estado: string[] }>(`${this.apiUrl}/estados/estados-cita`);
    }

    /******************************************************************************/
    /**************** Rutas para Dashboard de pacientes ***************************/
    /******************************************************************************/

    obtenerCitasDelEspecialistaAutenticado(): Observable<{ citas: CitaPorPaciente[] }> {
        return this.http.get<{ citas: CitaPorPaciente[] }>(`${this.apiUrl}/pacientes/citas/todas`);
    }

    obtenerCitasDelPacienteAutenticado(): Observable<{ citas: CitaPorEspecialista[] }> {
        return this.http.get<{ citas: CitaPorEspecialista[] }>(`${this.apiUrl}/listar-citas-paciente`);
    }

    cancelarCita(idCita: number): Observable<any> {
        return this.http.patch(`${this.apiUrl}/citas/${idCita}/cancelar`, {});
    }

    verPaciente(id: number): Observable<any> {
        return this.http.get(`${this.apiUrl}/pacientes/${id}`);
    }

    /**
     * Función para buscar un especialista por su id, devolviendo su nombre, especialidad, id de especialista y id de usuario
     * @param id id del especialista que estamos buscando
     * @returns devuelve un json con el especialista
     */
    verEspecialista(id: number): Observable<Especialista> {
        return this.http.get<{ especialista: Especialista }>(`${this.apiUrl}/especialistas/${id}`)
            .pipe(map(res => res.especialista));
    }

    /**
     * getHorasDisponibles() obtiene las horas disponibles de un especialista en una fecha específica.
     * A partir del ID del especialista y la fecha, devuelve un array de horas disponibles en formato 'HH:MM'.
     * si, idEspecialista es null, se omite el parámetro y el backend interpretara que el usuario logueado es el especialista.
     * @param idEspecialista El ID del especialista.
     * @param fecha La fecha en formato 'YYYY-MM-DD'.
     * @returns Devolución de un Observable con un array de horas disponibles.
     * Ejemplo de respuesta: ["09:00", "09:30", "10:00", "10:30", ...]
     */
    getHorasDisponibles(idEspecialista: number | null, fecha: string): Observable<{ horas_disponibles: string[] }> {
        let url = '';

        const rol = this.authService.obtenerRol(); // Asegúrate de tener este método en AuthService

        if (rol === 'especialista') {
            url = `${this.apiUrl}/especialista/horas-disponibles/${fecha}`;
        } else {
            if (idEspecialista === null) {
                throw new Error('Debe proporcionarse un ID de especialista para este rol.');
            }
            url = `${this.apiUrl}/horas-disponibles/${idEspecialista}/${fecha}`;
        }

        return this.http.get<{ horas_disponibles: string[] }>(url);
    }

    /**
     * obtener el especialista por el id
     */
    obtenerEspecialistaPorId(id: number): Observable<any> {
        return this.http.get(`${this.apiUrl}/especialistas/${id}`);
    }




    /******************************************************************************/
    /**************** Rutas para Dashboard de especialista ***************************/
    /******************************************************************************/



    obtenerCitasEspecialista(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/especialistas/me/citas`);
    }

    crearCita(data: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/citas`, data);
    }

    getPacientes(): Observable<Paciente[]> {
        return this.http.get<Paciente[]>('/api/pacientes/listado-minimo');
    }

    getEspecialistas(): Observable<Especialista[]> {
        return this.http.get<Especialista[]>('/especialistas/listado-minimo');
    }

    /**
     * getPacientesPorNombre() obtiene un array de pacientes con id de paciente, sus nombres y apellidos.
     * Muestra un listado de pacientes para poder seleccionar uno al crear una cita.
     * Cada paciente tiene la siguiente estructura:
     * {
     *  "pacientes": [
     *      {
     *          "id": 103,
     *          "nombre": "Margarita Alarcón"
     *      },
     *
     * @returns Observable con un array de pacientes con sus nombres y apellidos.
     */
    getPacientesPorNombre(): Observable<PacienteNombre[]> {
        return this.http.get<PacienteNombre[]>('/pacientespornombre');
    }

    getEspecialistaPorNombre(): Observable<EspecialistaNombre[]> {
        return this.http.get<EspecialistaNombre[]>('/especialistapornombre');
    }

    /**
     * getEspecialidades() obtiene un array de especialidades disponibles en la clínica
     * Permite listar las especialidades según los especialistas registrados 
     * para que el usuario pueda seleccionar una al crear una cita.
     * Devuelve un json con un array de especialidades, por ejemplo:
     * [
     *      "Endocrinología",
     *      "Nutrición",
     *      "Medicina General"
     * ]
     * @returns Observable con un array de especialidades.
     */
    getEspecialidades(): Observable<string[]> {
        return this.http.get<string[]>(`${this.apiUrl}/especialidades`);
    }

    /**
     * getEspecialistasPorEspecialidad() obtiene un array de especialistas filtrados por una especialidad específica.
     * Permite buscar especialistas por su especialidad para facilitar la creación de citas.
     * @param especialidad Nombre de la especialidad para filtrar los especialistas.
     * @returns 
     */
    getEspecialistasPorEspecialidad(especialidad: string): Observable<Especialista[]> {
        return this.http.get<Especialista[]>(`${this.apiUrl}/especialistas-por-especialidad?especialidad=${encodeURIComponent(especialidad)}`);
    }




    /******************************************************************************/
    /************************ Rutas para Configuración ****************************/
    /******************************************************************************/

    /**
     * getConfiguracion() Devuelve un json con un objeto que contiene las configuraciones generales de la clínica.
     * 
     * @returns Observable con un objeto que contiene las configuraciones generales de la clínica.
     */
    getConfiguracion(): Observable<{ message: string, configuraciones: Record<string, any> }> {
        return this.http.get<any>(`${this.apiUrl}/obtenerConfiguraciones`);
    }

    updateConfiguracion(id: number, data: { clave: string, valor: string, descripcion: string }): Observable<any> {
        // Usamos PUT o PATCH para actualizar un registro específico.
        // Aquí pasamos el ID de la configuración y un objeto con los datos a actualizar.
        return this.http.put<any>(`${this.apiUrl}/configuracion-general/${id}`, data);
    }

    /**
     * Actualiza el valor de una configuración por su clave.
     * @param clave Clave de la configuración
     * @param data Objeto con el nuevo valor: { valor: any }
     */
    updateConfiguracionPorClave(clave: string, data: { valor: any }): Observable<any> {
        return this.http.put<any>(`${this.apiUrl}/cambiarConfiguraciones/${clave}`, data);
    }

    /******************************************************************************/
    /******************* Rutas para Dashboard de Logs *****************************/
    /******************************************************************************/

    obtenerLogs(): Observable<Log[]> {
        return this.http.get<{ data: Log[] }>(`${this.apiUrl}/logs`).pipe(
            map(response => response.data)
        );
    }

    /******************************************************************************/
    /******************* Rutas para video Conferencias ****************************/
    /******************************************************************************/

    //Obtener la url de la sala, desde la tabla de Citas.
    obtenerNombreSalaCita(idCita: number): Observable<{ nombre_sala: string }> {
        return this.http.get<{ nombre_sala: string }>(`${this.apiUrl}/citas/${idCita}/sala-segura`);
    }
}
