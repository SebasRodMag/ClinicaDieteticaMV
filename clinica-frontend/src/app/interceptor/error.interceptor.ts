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
                let mensajeError = 'Ha ocurrido un error inesperado';
                const url = (error as any)?.url || ''; //algunos HttpErrorResponse traen la url

                const mensajeDelBackend =
                    error.error?.message ||
                    error.error?.error ||
                    error.error?.errors?.general?.[0] ||
                    null;

                switch (error.status) {
                    case 401: {
                        const mientrasLogin = req.url.includes('/login');
                        const mientrasRegistro = req.url.includes('/register');

                        if (mientrasLogin) {
                            //401 por credenciales inválidas
                            const backendMsg =
                                error.error?.message ||
                                error.error?.error ||
                                'Email o contraseña incorrectos.';
                            this.toastr.error(backendMsg, 'No se pudo iniciar sesión');
                            return throwError(() => error);
                        }

                        if (mientrasRegistro) {
                            const backendMsg = error.error?.message || 'No se pudo completar el registro.';
                            this.toastr.error(backendMsg, 'Registro');
                            return throwError(() => error);
                        }

                        //Resto de 401: sesiones caducadas en rutas protegidas
                        mensajeError = 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.';
                        this.authService.logout();
                        this.router.navigate(['/login']);
                        break;
                    }
                    case 403:
                        mensajeError = 'No tienes permiso para acceder a este recurso.';
                        this.router.navigate(['/']);
                        break;
                    case 0:
                        mensajeError = 'No se pudo conectar con el servidor.';
                        break;
                    case 400:
                        mensajeError = error.error?.message || 'Petición incorrecta.';
                        break;
                    case 404:
                        mensajeError = 'Recurso no encontrado.';
                        break;
                    case 422:
                        //El mismo componente gestiona los errores de formulario
                        mensajeError = 'Error de validación de datos.';
                        return throwError(() => error);
                    case 500:
                        mensajeError = 'Error interno del servidor.';
                        break;
                }

                console.error(`[ErrorInterceptor] ${error.status}: ${mensajeError}`, error);

                this.toastr.error(mensajeError, 'Error');

                return throwError(() => new Error(mensajeError));
            })
        );
    }
}
