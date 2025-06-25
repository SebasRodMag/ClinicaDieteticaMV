import { Component, OnInit, TemplateRef, ViewChild } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { UserService } from '../service/User-Service/user.service';
import { AuthService } from '../service/Auth-Service/Auth.service';
import { PacientesCitasComponent } from './pacientes-citas.component';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
  selector: 'app-paciente-citas',
  standalone: true,
  imports: [CommonModule, PacientesCitasComponent],
  templateUrl: './paciente-dashboard.component.html'
})
export class PacienteDashboardComponent {

  constructor(
    private UserService: UserService,
    private authService: AuthService,
    private snackBar: MatSnackBar 
  ) { }



  logout(): void {
    this.UserService.logout().subscribe({
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
