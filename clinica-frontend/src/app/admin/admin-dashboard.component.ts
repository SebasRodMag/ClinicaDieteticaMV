import { Component } from '@angular/core';
import { RouterModule, Route, Router } from '@angular/router';
import { AuthService } from '../service/Auth-Service/Auth.service';
import { UserService } from '../service/User-Service/user.service';
import { Usuario } from '../models/usuario.model';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [RouterModule, CommonModule],
  templateUrl: 'admin-dashboard-component.html',
  styleUrls: ['admin-dashboard-component.css'],

})
export class AdminDashboardComponent {

  usuario: Usuario | null = null;

  constructor(
    private authService: AuthService,
    private userService: UserService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.userService.getMe().subscribe({
      next: (user) => {
        this.usuario = user;
      },
      error: () => {
        this.usuario = null;
      },
    });
  }

  logout() {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}
