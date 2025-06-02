import { Component, OnInit, TemplateRef, ViewChild } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { UserService } from '../service/User-Service/user.service';
import { AuthService } from '../service/Auth-Service/Auth.service';
import { PacientesCitasComponent } from './pacientes-citas.component';

@Component({
  selector: 'app-paciente-citas',
  standalone: true,
  imports: [CommonModule, PacientesCitasComponent],
  templateUrl: './paciente-dashboard.component.html'
})
export class PacienteDashboardComponent {

  constructor(
    private userService: UserService,
    private authService: AuthService
  ) { }



  logout(){
    this.authService.logout();
  }
}
