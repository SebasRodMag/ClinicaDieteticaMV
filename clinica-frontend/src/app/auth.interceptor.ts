import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './service/Auth-Service/Auth.service';

const PUBLIC_ENDPOINTS = [
    '/login',
    '/register',
    '/color-tema',
];

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
    constructor(private authService: AuthService) { }

    intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
        if (req.method === 'OPTIONS' || PUBLIC_ENDPOINTS.some(p => req.url.includes(p))) {
            return next.handle(req);
        }

        const token = this.authService.obtenerToken();
        if (!token) return next.handle(req);

        const authReq = req.clone({
            setHeaders: { Authorization: `Bearer ${token}` }
        });

        return next.handle(authReq);
    }
}