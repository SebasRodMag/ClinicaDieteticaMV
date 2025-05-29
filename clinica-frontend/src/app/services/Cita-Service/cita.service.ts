// src/app/services/citas.service.ts

import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';

export interface Cita {
  id: number;
  fecha_hora_cita: string;
  estado: string;
  paciente: {
    id: number;
    nombre: string;
    apellidos: string;
  };
}

@Injectable({
  providedIn: 'root'
})
export class CitasService {
  private apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  obtenerCitasPorMedico(
      medicoId: number,
      fecha?: string,
      page: number = 1,
      pageSize: number = 10
    ): Observable<{ total: number; data: Cita[] }> {
      let params = new HttpParams()
        .set('page', page)
        .set('pageSize', pageSize);

      if (fecha) {
        params = params.set('fecha', fecha);
      }

      return this.http.get<{ total: number; data: Cita[] }>(
        `${this.apiUrl}/medicos/${medicoId}/citas`,
        { params }
      );
  }

  actualizarEstadoCita(idCita: number, nuevoEstado: string): Observable<any> {
    return this.http.patch(`${this.apiUrl}/citas/${idCita}/estado`, {
      estado: nuevoEstado
    });
  }

}
