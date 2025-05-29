import { Injectable } from '@angular/core';
import { CanActivate, CanActivateChild, Router, UrlTree, ActivatedRouteSnapshot } from '@angular/router';
import { Observable } from 'rxjs';
import { AuthService } from './service/Auth-Service/Auth.service';

@Injectable({
  providedIn: 'root',
})
export class AuthGuard implements CanActivate, CanActivateChild {
  constructor(private authService: AuthService, private router: Router) {}

  private checkLogin(route?: ActivatedRouteSnapshot):
    | boolean
    | UrlTree
    | Observable<boolean | UrlTree>
    | Promise<boolean | UrlTree> {
    if (!this.authService.isLoggedIn()) {
      console.warn('Acceso denegado: Usuario no autenticado');
      return this.router.createUrlTree(['/login']);
    }

    if (route?.data?.['roles']) {
      const userRole = this.authService.getUserRole();
      if (!route.data['roles'].includes(userRole)) {
        console.warn('Acceso denegado: Rol no autorizado');
        //Redirige a home
        return this.router.createUrlTree(['/']);
      }
    }
    return true;
  }

  canActivate(route: ActivatedRouteSnapshot):
    | boolean
    | UrlTree
    | Observable<boolean | UrlTree>
    | Promise<boolean | UrlTree> {
    return this.checkLogin(route);
  }

  canActivateChild(route: ActivatedRouteSnapshot):
    | boolean
    | UrlTree
    | Observable<boolean | UrlTree>
    | Promise<boolean | UrlTree> {
    return this.checkLogin(route);
  }
}
