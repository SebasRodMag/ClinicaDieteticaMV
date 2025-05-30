/**
 * AuthService
 * Servicio que gestiona la autenticación del usuario en la aplicación Angular.
 * Incluye login, registro, logout, y utilidades para manejar el token y los datos del usuario.
 * 
 * @author 
 * Sebastián Rodríguez
 * @version 
 * 1.0
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
    // Puedes agregar más campos si el backend los devuelve (dni, dirección, etc.)
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

    constructor(private http: HttpClient) {}

    // ========================
    // Login
    // ========================
    login(credentials: { email: string; password: string }): Observable<LoginResponse> {
        const headers = new HttpHeaders({ 'Content-Type': 'application/json' });

        return this.http.post<LoginResponse>(`${this.apiUrl}/login`, credentials, { headers })
            .pipe(
                tap((response: LoginResponse) => {
                    if (response?.access_token && response?.user) {
                        localStorage.setItem(this.tokenKey, response.access_token);
                        localStorage.setItem(this.userKey, JSON.stringify(response.user));
                    }
                })
            );
    }

    // ========================
    // Registro de nuevos usuarios
    // ========================
    register(credentials: { nombre: string; apellidos: string; email: string; password: string; password_confirmation: string }): Observable<LoginResponse> {
        const headers = new HttpHeaders({ 'Content-Type': 'application/json' });

        return this.http.post<LoginResponse>(`${this.apiUrl}/register`, credentials, { headers })
            .pipe(
                tap((response: LoginResponse) => {
                    if (response?.access_token && response?.user) {
                        localStorage.setItem(this.tokenKey, response.access_token);
                        localStorage.setItem(this.userKey, JSON.stringify(response.user));
                    }
                })
            );
    }

    // ========================
    // Logout
    // ========================
    logout(): void {
        localStorage.removeItem(this.tokenKey);
        localStorage.removeItem(this.userKey);
    }

    // ========================
    // Estado de autenticación
    // ========================
    isLoggedIn(): boolean {
        return !!localStorage.getItem(this.tokenKey);
    }

    // ========================
    // Obtener token JWT
    // ========================
    getToken(): string | null {
        return localStorage.getItem(this.tokenKey);
    }

    // ========================
    // Obtener objeto usuario
    // ========================
    getUser(): User | null {
        const user = localStorage.getItem(this.userKey);
        return user ? JSON.parse(user) : null;
    }

    // ========================
    // Obtener rol del usuario
    // ========================
    getUserRole(): string | null {
        return this.getUser()?.rol || null;
    }
}
