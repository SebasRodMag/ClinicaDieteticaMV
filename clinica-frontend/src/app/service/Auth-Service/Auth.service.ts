import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { tap, map } from 'rxjs/operators';
import { Observable } from 'rxjs';


interface User {
    id: number;
    nombre: string;
    email: string;
    rol: string;
}
interface LoginResponse {
    token: string;
    user: any;
}

@Injectable({
    providedIn: 'root'
})
export class AuthService {
    private apiUrl = 'http://127.0.0.1:8000/api';
    private tokenKey = 'auth_token';

    constructor(private http: HttpClient) { }

    login(credentials: { email: string, password: string }): Observable<LoginResponse> {
    const headers = new HttpHeaders({ 'Content-Type': 'application/json' });
    return this.http.post<LoginResponse>(`${this.apiUrl}/auth/login`, credentials, { headers })
        .pipe(
            tap((response: LoginResponse) => {
                localStorage.setItem(this.tokenKey, response.token);
                localStorage.setItem('user', JSON.stringify(response.user));
            })
        );
}

    logout(): void {
        localStorage.removeItem(this.tokenKey);
    }

    isLoggedIn(): boolean {
        return !!localStorage.getItem(this.tokenKey);
    }

    getToken(): string | null {
        return localStorage.getItem(this.tokenKey);
    }

    getUserRole(): string | null {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user).rol : null;
    }
}
