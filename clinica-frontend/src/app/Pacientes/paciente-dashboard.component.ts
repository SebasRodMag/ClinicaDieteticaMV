import { Component, OnInit, TemplateRef, ViewChild } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { UserService } from '../service/User-Service/user.service';
import { AuthService } from '../service/Auth-Service/Auth.service';
import { PacientesCitasComponent } from './pacientes-citas.component';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Usuario } from '../models/usuario.model';
import { Router, RouterModule } from '@angular/router';

@Component({
  selector: 'app-paciente-citas',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './paciente-dashboard.component.html',
  styleUrls: ['./paciente-dashboard.component.css'],
})
export class PacienteDashboardComponent {

  usuario: Usuario | null = null;

  constructor(
    private userService: UserService,
    private authService: AuthService,
    private snackBar: MatSnackBar,
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

  logout(): void {
    this.userService.logout().subscribe({
      next: () => {
        this.authService.logout();
        this.mostrarMensaje('Sesión cerrada correctamente', 'success');
        window.location.href = '/login';
      },
      error: () => {
        this.mostrarMensaje('Error al cerrar sesión', 'error');
      }
    });
  }

  mostrarMensaje(mensaje: string, tipo: 'success' | 'error') {
    this.snackBar.open(mensaje, 'Cerrar', {
      duration: 3000,
      panelClass: tipo === 'success' ? ['snackbar-' + tipo] : undefined,
    });
  }
}
