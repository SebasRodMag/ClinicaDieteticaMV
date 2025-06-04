import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Paciente } from '../../models/paciente.model';
import { Usuario } from '../../models/usuario.model';
import { Especialista } from '../../models/especialista.model';
import { EspecialistaList } from '../../models/especialistaList.model';
import { UsuarioDisponible } from '../../models/usuarioDisponible.model';


@Injectable({
    providedIn: 'root'
})
export class UserService {
    private apiUrl = 'http://localhost:8000/api';

    constructor(private http: HttpClient) { }

    getUsuarios(): Observable<Usuario[]> {
        return this.http.get<Usuario[]>(`${this.apiUrl}/usuarios`);
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

    crearUsuario(usuario: Usuario): Observable<Usuario> {
        return this.http.post<Usuario>(`${this.apiUrl}/usuarios`, usuario);
    }

    actualizarUsuario(usuario: Usuario): Observable<Usuario> {
        return this.http.put<Usuario>(`${this.apiUrl}/usuarios/${usuario.id}`, usuario);
    }

    getListarEspecialistas(): Observable<EspecialistaList[]> {
        return this.http.get<EspecialistaList[]>(`${this.apiUrl}/especialistasfull`);
    }

    crearEspecialista(data: { user_id: number, especialidad: string }): Observable<any> {
        return this.http.post(`${this.apiUrl}/especialistas`, data);
    }

    getUsuariosSinRolEspecialistaNiPaciente(): Observable<UsuarioDisponible[]> {
        return this.http.get<UsuarioDisponible[]>(`${this.apiUrl}/usuarios/listar/usuarios`);
    }

    /******************************************************************************/
    /**************** Rutas para Dashboard de pacientes ***************************/
    /******************************************************************************/

    obtenerCitasDelUsuarioAutenticado(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/pacientes/citas/todas`);
    }

    cancelarCita(idCita: number): Observable<any> {
        return this.http.patch(`${this.apiUrl}/citas/${idCita}/cancelar`, {});
    }

    verPaciente(id: number): Observable<any>{
        return this.http.get(`${this.apiUrl}/pacientes/${id}`);
    }


    /******************************************************************************/
    /**************** Rutas para Dashboard de especialista ***************************/
    /******************************************************************************/



    obtenerCitasEspecialista(): Observable<any[]> {
        return this.http.get<any[]>(`${this.apiUrl}/especialistas/me/citas`);
    }

    crearCita(data: any): Observable<any> {
        return this.http.post('/api/citas', data);
    }

    getPacientes(): Observable<Paciente[]> {
        return this.http.get<Paciente[]>('/api/pacientes/listado-minimo');
    }

    getEspecialistas(): Observable<Especialista[]> {
        return this.http.get<Especialista[]>('/api/especialistas/listado-minimo');
    }
}
