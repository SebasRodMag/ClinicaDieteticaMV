import { Injectable } from '@angular/core';
import {
    HttpEvent,
    HttpInterceptor,
    HttpHandler,
    HttpRequest,
    HttpErrorResponse
} from '@angular/common/http';
import { Observable, catchError, throwError } from 'rxjs';
import { Router } from '@angular/router';
import { AuthService } from '../service/Auth-Service/Auth.service';
import { ToastrService } from 'ngx-toastr';

@Injectable()
export class ErrorInterceptor implements HttpInterceptor {

    constructor(
        private authService: AuthService,
        private router: Router,
        private toastr: ToastrService
    ) { }

    intercept(
        req: HttpRequest<any>,
        next: HttpHandler
    ): Observable<HttpEvent<any>> {
        return next.handle(req).pipe(
            catchError((error: HttpErrorResponse) => {
                let errorMessage = 'Ha ocurrido un error inesperado';

                switch (error.status) {
                    case 401:
                        errorMessage = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.';
                        this.authService.logout();
                        this.router.navigate(['/login']);
                        break;
                    case 403:
                        errorMessage = 'No tienes permiso para acceder a este recurso.';
                        this.router.navigate(['/']);
                        break;
                    case 0:
                        errorMessage = 'No se pudo conectar con el servidor.';
                        break;
                    case 400:
                        errorMessage = error.error?.message || 'Petición incorrecta.';
                        break;
                    case 404:
                        errorMessage = 'Recurso no encontrado.';
                        break;
                    case 500:
                        errorMessage = 'Error interno del servidor.';
                        break;
                }

                console.error(`[ErrorInterceptor] ${error.status}: ${errorMessage}`, error);

                this.toastr.error(errorMessage, 'Error');

                return throwError(() => new Error(errorMessage));
            })
        );
    }
}
