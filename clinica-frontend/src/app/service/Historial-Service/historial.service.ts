import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of, map, catchError } from 'rxjs';
import { Historial } from '../../models/historial.model';
import { Paciente } from '../../models/paciente.model';
import { urlApiServicio } from '../../components/utilidades/variable-entorno';

@Injectable({
    providedIn: 'root'
})
export class HistorialService {
    private apiUrl = urlApiServicio.apiUrl;

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

    /**
     * Obtener la lista de pacientes de un especialista logueado
     */
    obtenerPacientesEspecialista(): Observable<Paciente[]> {
        return this.http.get<{ data: Paciente[] }>(`${this.apiUrl}/paciente-por-especialista/`)
            .pipe(map(response => response.data));
    }

    /**
     * Obtener el historial del paciente según el id facilitado
     * @param pacienteId 
     * @returns 
     */
    obtenerUltimoHistorialPorPaciente(pacienteId: number) {
        return this.obtenerHistorialesEspecialista().pipe(
            map((historiales: Historial[]) => {
                const delPaciente = historiales.filter(h => h.paciente?.id === pacienteId);
                if (delPaciente.length === 0) return null;

                // Ordena por fecha desc (YYYY-MM-DD) y, de empate, por id desc
                delPaciente.sort((a, b) => {
                    const fa = a.fecha ?? '';
                    const fb = b.fecha ?? '';
                    if (fa < fb) return 1;
                    if (fa > fb) return -1;
                    return (b.id ?? 0) - (a.id ?? 0);
                });

                return delPaciente[0] ?? null;
            }),
            catchError(() => of(null))
        );
    }

}
