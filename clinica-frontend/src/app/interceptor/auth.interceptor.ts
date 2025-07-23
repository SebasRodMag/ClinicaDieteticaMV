
/**   
 * El archivo interceptor.ts se encarga de interceptar las solicitudes HTTP
 * y agregar el token de autenticación a las cabeceras de las solicitudes 
 * que requieren autenticación.
 * 
 */
import { Injectable } from '@angular/core';
import {
    HttpInterceptor,
    HttpRequest,
    HttpHandler,
    HttpEvent
} from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from '../service/Auth-Service/Auth.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {

    constructor(private authService: AuthService) { }

    intercept(
        req: HttpRequest<any>,
        next: HttpHandler
    ): Observable<HttpEvent<any>> {
        const token = this.authService.obtenerToken();

        // Se excluyen las rutas públicas como login y register
        if (req.url.includes('/login') || req.url.includes('/register')) {
            return next.handle(req);
        }

        if (token) {
            const authReq = req.clone({
                setHeaders: {
                    Authorization: `Bearer ${token}`
                }
            });
            return next.handle(authReq);
        }

        return next.handle(req);
    }
}
