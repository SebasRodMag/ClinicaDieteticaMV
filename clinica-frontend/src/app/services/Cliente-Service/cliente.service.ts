import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
    providedIn: 'root',
})
export class ClienteService {
    private apiUrl = 'http://localhost:8000/api/clientes';

    constructor(private http: HttpClient) {}

    private getAuthHeaders(): HttpHeaders {
        const token = localStorage.getItem('token'); // ⚠️ Asegúrate que el token esté en localStorage
        return new HttpHeaders({
            Authorization: `Bearer ${token}`,
        });
    }

    getClientes(): Observable<any[]> {
        return this.http.get<any[]>(this.apiUrl, {
            headers: this.getAuthHeaders(),
        });
    }

    getPacientesDelCliente(clienteId: string): Observable<any> {
        return this.http.get(`${this.apiUrl}/${clienteId}/pacientes`);
    }
}
