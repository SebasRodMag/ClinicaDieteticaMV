import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { urlApiServicio } from '../../components/utilidades/variable-entorno';
import { Observable } from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class DocumentoService {
    private apiUrl = urlApiServicio.apiUrl;

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
}
