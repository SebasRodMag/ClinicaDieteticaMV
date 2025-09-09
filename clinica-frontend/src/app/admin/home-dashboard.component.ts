import { Component, OnInit } from '@angular/core';
import { ConfiguracionService } from '../service/Config-Service/configuracion.service';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-dashboard-home',
  templateUrl: './home-dashboard.component.html',
  styleUrls: ['./home-dashboard.component.css'],
  standalone: true,
  imports: [CommonModule, RouterModule,],
})
export class HomeDashboardComponent implements OnInit {
  resumen = [
    { titulo: 'Total Usuarios', valor: '...', icono: 'usuarios', clase: 'resumen-card' },
    { titulo: 'Especialistas activos', valor: '...', icono: 'especialistas', clase: 'resumen-card' },
    { titulo: 'Pacientes activos', valor: '...', icono: 'pacientes', clase: 'resumen-card' },
    { titulo: 'Citas hoy', valor: '...', icono: 'citas', clase: 'resumen-card' },
  ];

  accesos = [
    { titulo: 'Gestión de Usuarios', ruta: '/administrador/usuarios' },
    { titulo: 'Gestión de Citas', ruta: '/administrador/citas' },
    { titulo: 'Gestión de Pacientes', ruta: '/administrador/pacientes' },
    { titulo: 'Configuración', ruta: '/administrador/configuracion' },
  ];

  loading = true;
  imagenesCargadas = 0;
  datosCargados = false;

  constructor(private ConfiguracionService: ConfiguracionService, private http: HttpClient) { }

  ngOnInit(): void {
    this.ConfiguracionService.getResumenDashboard().subscribe({
      next: data => {
        this.resumen[0].valor = `${data.total_usuarios} registrados`;
        this.resumen[1].valor = `${data.especialistas} activos`;
        this.resumen[2].valor = `${data.pacientes} activos`;
        this.resumen[3].valor = `${data.citas_hoy} programadas`;

        this.datosCargados = true;
        this.comprobarCargaCompleta();
      },
      error: () => {
        console.warn('Error al cargar los datos del resumen.');
        this.datosCargados = true;
        this.comprobarCargaCompleta();
      }
    });
  }

  //Estos métodos son unicamente para manejar la carga de imágenes y ocultar el loading cuando todas las imágenes estén listas.
  onImagenCargada(): void {
    this.imagenesCargadas++;
    this.comprobarCargaCompleta();
  }

  comprobarCargaCompleta(): void {
    if (this.datosCargados && this.imagenesCargadas >= this.resumen.length) {
      this.loading = false;
    }
  }

}
