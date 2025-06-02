/**
 * AuthService
 * Servicio que gestiona la autenticación del usuario en la aplicación Angular.
 * Incluye login, registro, logout, y utilidades para manejar el token y los datos del usuario.
 * 
 * @author 
 * Sebastián Rodríguez
 * @version 
 * 1.1
 * @date 
 * 2025-05-30
 */

import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

// Interfaces
export interface User {
    id: number;
    nombre: string;
    apellidos: string;
    email: string;
    rol: string;
}

export interface LoginResponse {
    access_token: string;
    user: User;
}

@Injectable({
    providedIn: 'root'
})
export class AuthService {
    private apiUrl = 'http://127.0.0.1:8000/api';
    private tokenKey = 'auth_token';
    private userKey = 'user';

    constructor(private http: HttpClient) { }

    // ========================
    // Login
    // ========================
    login(credentials: { email: string; password: string }): Observable<LoginResponse> {
        const headers = new HttpHeaders({ 'Content-Type': 'application/json' });

        return this.http.post<LoginResponse>(`${this.apiUrl}/login`, credentials, { headers })
            .pipe(
                tap((response: LoginResponse) => {
                    if (response?.access_token && response?.user) {
                        this.setItem(this.tokenKey, response.access_token);
                        this.setItem(this.userKey, response.user);
                        console.log('Login exitoso:', response.user);
                    } else {
                        console.error('Error en la respuesta del login:', response);
                    }
                })
            );
    }

    // ========================
    // Registro
    // ========================
    register(credentials: { nombre: string; apellidos: string; email: string; password: string; password_confirmation: string }): Observable<LoginResponse> {
        const headers = new HttpHeaders({ 'Content-Type': 'application/json' });

        return this.http.post<LoginResponse>(`${this.apiUrl}/register`, credentials, { headers })
            .pipe(
                tap((response: LoginResponse) => {
                    if (response?.access_token && response?.user) {
                        this.setItem(this.tokenKey, response.access_token);
                        this.setItem(this.userKey, response.user);
                    }
                })
            );
    }

    // ========================
    // Logout
    // ========================
    logout(): void {
        sessionStorage.removeItem(this.tokenKey);
        sessionStorage.removeItem(this.userKey);
    }

    // ========================
    // Estado de autenticación
    // ========================
    isLoggedIn(): boolean {
        return !!this.getToken();
    }

    // ========================
    // Obtener token JWT
    // ========================
    getToken(): string | null {
        return this.getItem<string>(this.tokenKey);
    }

    // ========================
    // Obtener objeto usuario
    // ========================
    getUser(): User | null {
        return this.getItem<User>(this.userKey);
    }

    // ========================
    // Obtener rol del usuario
    // ========================
    getUserRole(): string | null {
        return this.getUser()?.rol || null;
    }

    // ========================
    // Headers autorizados para llamadas protegidas
    // ========================
    getAuthHeaders(): HttpHeaders {
        const token = this.getToken();
        return new HttpHeaders({
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        });
    }

    // ========================
    // Obtener el id del usuario logueado
    // ========================
    getUserId(): number | null {
        return this.getUser()?.id || null;
    }

    // ========================
    // Helpers: Storage
    // ========================
    private setItem(key: string, value: any): void {
        sessionStorage.setItem(key, JSON.stringify(value));
    }

    private getItem<T>(key: string): T | null {
        const raw = sessionStorage.getItem(key);
        return raw ? JSON.parse(raw) : null;
    }
}
