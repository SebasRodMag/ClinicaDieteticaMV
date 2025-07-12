import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { Historial } from '../../models/historial.model';

@Injectable({
    providedIn: 'root'
})
export class HistorialService {
    private apiUrl = 'http://localhost:8000/api';

    constructor(private http: HttpClient) { }

    /**
     * Obtener todos los historiales de pacientes de un especialista autenticado.
     */
    obtenerHistorialesEspecialista(): Observable<Historial[]> {
        return this.http.get<{ data: Historial[] }>(`${this.apiUrl}/historial-paciente/`)
            .pipe(map(response => response.data));
    }

    /**
     * Obtener los historiales del paciente autenticado.
     */
    obtenerMisHistorialesPaciente(): Observable<Historial[]> {
        return this.http.get<{ data: Historial[] }>(`${this.apiUrl}/mis-historiales`)
            .pipe(map(response => response.data));
    }

    /**
     * Crear una nueva entrada de historial.
     * @param historial Datos del historial a crear.
     */
    crearHistorial(historial: Partial<Historial>): Observable<any> {
        return this.http.post(`${this.apiUrl}/historial/`, historial);
    }

    /**
     * Actualizar una entrada de historial existente.
     * @param id ID del historial.
     * @param historial Datos a actualizar.
     */
    actualizarHistorial(id: number, historial: Partial<Historial>): Observable<any> {
        return this.http.put(`${this.apiUrl}/historial/${id}`, historial);
    }

    /**
     * Eliminar una entrada de historial.
     * @param id ID del historial a eliminar.
     */
    eliminarHistorial(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/historial/${id}`);
    }
}
