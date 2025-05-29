/**
 * rele.guard.ts
 * Guard que protege las rutas basadas en el rol del usuario.
 * Verifica si el usuario tiene el rol requerido para acceder a la ruta.
 * Si no tiene el rol adecuado, redirige al usuario a la página de inicio de sesión. 
 * 
 */
import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root',
})

/**
 * RoleGuard, Clase que implementa CanActivate para proteger rutas basadas en el rol del usuario.
 * Verifica si el usuario tiene el rol requerido para acceder a la ruta.
 * Si no tiene el rol adecuado, redirige al usuario a la página de inicio de sesión.
 */
export class RoleGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): boolean {
    const expectedRoles = route.data['roles'] as string[];
    const userRole = this.authService.getRol();

    if (!userRole || !expectedRoles.includes(userRole)) {
      console.log('Acceso denegado. Rol no autorizado.');
      //Si el rol no coincide, redirigir a la página de login
      this.router.navigate(['/login']); // o muestra página de acceso denegado
      return false;
    }

    return true;
  }
}
