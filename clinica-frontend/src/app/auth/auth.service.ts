/**
 * AuthService
 * Servicio de autenticación para manejar el inicio de sesión, cierre de sesión y verificación del estado del usuario.
 * Este servicio interactúa con la API de autenticación y almacena el token y rol del usuario en localStorage.
 * Proporciona métodos para redirigir al usuario según su rol y verificar si el usuario está autenticado.
 */
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { BehaviorSubject, Observable } from 'rxjs';
import { tap } from 'rxjs/operators';

/**
 * Interfaz que define la estructura del usuario autenticado.
 * Incluye propiedades como id, email, fecha de creación, fecha de actualización,   
 * fecha de eliminación, rol y nombre.
 * @interface User aplica a la respuesta del backend al iniciar sesión.
 * @property {number} id - Identificador único del usuario.
 * @property {string} email - Correo electrónico del usuario.
 * @property {string | null} email_verified_at - Fecha de verificación del correo electrónico (puede ser nulo).
 * @property {string} created_at - Fecha de creación del usuario.
 * @property {string} updated_at - Fecha de la última actualización del usuario.
 * @property {string | null} deleted_at - Fecha de eliminación del usuario (puede ser nulo).
 * @property {string} rol - Rol del usuario (por ejemplo, 'Administrador', 'Medico', 'Cliente').
 * @property {string} name - Nombre del usuario.
 */
interface User {
    id: number;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    rol: string; // Este es el rol que obtienes de la respuesta
    name: string;
}

/**
 * Interfaz que define la estructura de la respuesta de autenticación.
 * Incluye un token de acceso y el usuario autenticado.
 * @property {string} token - Token de acceso JWT.
 * @property {User} user - Objeto que representa al usuario autenticado.
 * @interface AuthResponse aplica a la respuesta del backend al iniciar sesión.
 */
interface AuthResponse {
    token: string;
    user: User;
}

/**
 * Servicio de autenticación para manejar el inicio de sesión, cierre de sesión y verificación del estado del usuario.
 * Este servicio interactúa con la API de autenticación y almacena el token y rol del usuario en localStorage.
 * Proporciona métodos para redirigir al usuario según su rol y verificar si el usuario está autenticado.
 * @class AuthService
 * @description Servicio que maneja la autenticación de usuarios en la aplicación.
 * 
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
    private apiUrl = 'http://localhost:8000/api/auth'; // Ajusta según tu backend

    constructor(private http: HttpClient, private router: Router) {}

    login(credentials: {
        email: string;
        password: string;
    }): Observable<AuthResponse> {
        return this.http
            .post<AuthResponse>(`${this.apiUrl}/login`, credentials)
            .pipe(
                tap((res) => {
                    // Almacenar token y rol en localStorage
                    localStorage.setItem('token', res.token);
                    localStorage.setItem('usuarioId', res.user.id.toString());
                    localStorage.setItem('rol', res.user.rol);
                    localStorage.setItem('name', res.user.name);

                    // Redirigir al usuario según su rol
                    this.redirectUser(res.user.rol);
                })
            );
    }

    redirectUser(rol: string) {
        switch (rol) {
            case 'Administrador':
                this.router.navigate(['/admin/dashboard']);
                break;
            case 'Medico':
                this.router.navigate(['/medico/dashboard']);
                break;
            case 'Cliente':
                this.router.navigate(['/cliente/dashboard']);
                break;
            default:
                this.router.navigate(['/login']);
                break;
        }
    }

    /**
     * Método para cerrar sesión del usuario.
     * Limpia el localStorage y redirige al usuario a la página de inicio de sesión.
     */
    logout() {
        localStorage.clear();
        this.router.navigate(['/login']);
    }

    /**
     * Método para obtener el Rol del usuario autenticado.
     * @returns El ID del usuario almacenado en localStorage.
     * Si no hay ID almacenado, devuelve mensaje.
     */
    getRol(): string {
        return localStorage.getItem('rol') || 'no existe un rol';
    }

    /**
     * Método para obtener el ID del usuario autenticado.
     * @returns Un booleano que indica si el usuario está autenticado.
     * Devuelve true si hay un token en localStorage, false en caso contrario.
     */
    isLoggedIn(): boolean {
        return !!localStorage.getItem('token');
    }

    /**
     * Método para obtener el Usuario autenticado.
     * @returns Un observable que emite el usuario autenticado.
     * Realiza una solicitud HTTP GET a la API para obtener los detalles del usuario autenticado.
     */
    me(): Observable<User> {
        return this.http.get<User>(`${this.apiUrl}/me`).pipe(tap((user) => {}));
    }
}
