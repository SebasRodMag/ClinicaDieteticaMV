import { Component } from '@angular/core';
import { RouterModule, Route, Router } from '@angular/router';
import { AuthService } from '../service/Auth-Service/Auth.service';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [RouterModule],
  templateUrl: 'admin-dashboard-component.html',

})
export class AdminDashboardComponent {

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  logout() {
  this.authService.logout();
  this.router.navigate(['/login']);
}
}
