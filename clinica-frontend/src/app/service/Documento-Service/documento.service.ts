import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { Documento } from '../../models/documento.model';

@Injectable({
    providedIn: 'root'
})
export class DocumentoService {
    private apiUrl = environment.apiBase;

    constructor(private http: HttpClient) { }

    subirDocumento(formData: FormData): Observable<any> {
        return this.http.post(`${this.apiUrl}/documentos`, formData);
    }

    obtenerMisDocumentos(): Observable<any> {
        return this.http.get(`${this.apiUrl}/mis-documentos`);
    }

    descargarDocumento(id: number): Observable<Blob> {
        return this.http.get(`${this.apiUrl}/documentos/${id}/descargar`, { responseType: 'blob' });
    }

    eliminarDocumento(id: number): Observable<any> {
        return this.http.delete(`${this.apiUrl}/documentos/${id}`);
    }

    obtenerDocumentosDePaciente(pacienteId: number) {
        return this.http.get<{ documentos: Documento[]; message?: string }>(
            `${this.apiUrl}/pacientes/${pacienteId}/documentos`
        ).pipe(
            map(res => res.documentos ?? [])
        );
    }
}
