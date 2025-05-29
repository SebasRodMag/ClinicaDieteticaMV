import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
    providedIn: 'root',
})
export class PacienteService {
    private apiUrl = 'http://localhost:8000/api/pacientes';

    constructor(private http: HttpClient) {}

    // Obtener pacientes paginados
    obtenerPacientes(pagina: number = 1, limite: number = 10): Observable<any> {
        return this.http.get<any>(`${this.apiUrl}?page=${pagina}&limit=${limite}`);
    }

    // Obtener detalles de un paciente por ID
    obtenerPacientePorId(id: number): Observable<any> {
        return this.http.get<any>(`${this.apiUrl}/${id}`);
    }

    // Actualizar datos de un paciente
    actualizarPaciente(id: number, datos: any): Observable<any> {
        return this.http.put<any>(`${this.apiUrl}/${id}`, datos);
    }

    // Crear nuevo paciente
    crearPaciente(datos: any): Observable<any> {
        return this.http.post<any>(this.apiUrl, datos);
    }

    // Eliminar paciente
    eliminarPaciente(id: number): Observable<any> {
        return this.http.delete<any>(`${this.apiUrl}/${id}`);
    }
}
