/**
 * AuthGuard
 * @description Protege rutas según la autenticación del usuario y su rol.
 * Redirige al login si el usuario no está autenticado, y al home si el rol no está autorizado.
 * 
 * Uso en rutas:
 * {
 *   path: 'admin',
 *   canActivate: [AuthGuard],
 *   data: { roles: ['administrador'] }
 * }
 * 
 * @author 
 * Sebastián Rodríguez
 */

import { Injectable } from '@angular/core';
import { CanActivate, CanActivateChild, Router, UrlTree, ActivatedRouteSnapshot } from '@angular/router';
import { Observable } from 'rxjs';
import { AuthService } from './service/Auth-Service/Auth.service';

@Injectable({
  providedIn: 'root',
})
export class AuthGuard implements CanActivate, CanActivateChild {

  constructor(private authService: AuthService, private router: Router) {}

  /**
   * Verifica si el usuario puede acceder a la ruta.
   * @param route Información de la ruta, incluyendo roles autorizados.
   * @returns boolean o UrlTree para redirigir.
   */
  private checkLogin(
    route?: ActivatedRouteSnapshot
  ): boolean | UrlTree | Observable<boolean | UrlTree> | Promise<boolean | UrlTree> {
    if (!this.authService.isLoggedIn()) {
      console.warn('[AuthGuard] Acceso denegado: Usuario no autenticado.');
      return this.router.createUrlTree(['/login']);
    }

    const allowedRoles: string[] | undefined = route?.data?.['roles'];
    const userRole = this.authService.obtenerRol();

    if (allowedRoles && (!userRole || !allowedRoles.includes(userRole))) {
      console.warn(`[AuthGuard] Acceso denegado: Rol '${userRole}' no autorizado para esta ruta.`);
      return this.router.createUrlTree(['/']);
    }

    return true;
  }

  canActivate(
    route: ActivatedRouteSnapshot
  ): boolean | UrlTree | Observable<boolean | UrlTree> | Promise<boolean | UrlTree> {
    return this.checkLogin(route);
  }

  canActivateChild(
    route: ActivatedRouteSnapshot
  ): boolean | UrlTree | Observable<boolean | UrlTree> | Promise<boolean | UrlTree> {
    return this.checkLogin(route);
  }
}
