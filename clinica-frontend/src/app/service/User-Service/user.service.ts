import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Paciente } from '../../models/paciente.model';

export interface Usuario {
    id: number;
    nombre: string;
    apellidos: string;
    dni: string;
    telefono: string;
    email: string;
    rol: string;
}

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


    updateRolUsuario(id: number, rol: string): Observable<any> {
        return this.http.put(`${this.apiUrl}/usuarios/${id}/rol`, { rol });
    }


    eliminarUsuario(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/usuarios/${id}`);
    }

    pacientesConEspecialista(): Observable<Paciente[]> {
        return this.http.get<Paciente[]>(`${this.apiUrl}/pacienteslistado`);
    }

    actualizarPaciente(paciente: Paciente) {
        return this.http.put<Paciente>(`/api/pacientes/${paciente.id}`, paciente);
    }
}
