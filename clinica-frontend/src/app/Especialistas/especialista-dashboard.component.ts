import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { UserService } from '../service/User-Service/user.service';
import { AuthService } from '../service/Auth-Service/Auth.service';
import { EspecialistaCitasComponent } from './especialista-citas.component';
import { Usuario } from '../models/usuario.model';
import { Router } from '@angular/router';
import { RouterModule } from '@angular/router';
import { HistorialListComponent } from './historial-list.component';

@Component({
  selector: 'app-especialista-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './especialista-dashboard.component.html',
  styleUrls: ['./especialista-dashboard.component.css'],
})
export class EspecialistaDashboardComponent {

  usuario: Usuario | null = null;

  constructor(
    private userService: UserService,
    private authService: AuthService,
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
