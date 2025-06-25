import { Component, OnInit } from '@angular/core';
import { ConfiguracionService } from '../service/Config-Service/configuracion.service';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { LucideAngularModule } from 'lucide-angular';

@Component({
  selector: 'app-dashboard-home',
  templateUrl: './home-dashboard.component.html',
  styleUrls: ['./home-dashboard.component.css'],
  standalone: true,
  imports: [CommonModule, RouterModule, LucideAngularModule],
})
export class HomeDashboardComponent implements OnInit {
  resumen = [
  { titulo: 'Total Usuarios', valor: '...', bgColor: 'bg-primary', iconClass: 'bi-people' },
  { titulo: 'Especialistas activos', valor: '...', bgColor: 'bg-success', iconClass: 'bi-person-check' },
  { titulo: 'Pacientes activos', valor: '...', bgColor: 'bg-info', iconClass: 'bi-person' },
  { titulo: 'Citas hoy', valor: '...', bgColor: 'bg-danger', iconClass: 'bi-calendar-day' }
];

  accesos = [
    { titulo: 'Gestión de Usuarios', ruta: '/admin/usuarios' },
    { titulo: 'Gestión de Citas', ruta: '/admin/citas' },
    { titulo: 'Documentos Subidos', ruta: '/admin/documentos' },
    { titulo: 'Configuración', ruta: '/admin/configuracion' },
  ];

  constructor(private ConfiguracionService: ConfiguracionService) { }

  ngOnInit(): void {
    this.ConfiguracionService.getResumenDashboard().subscribe(data => {
      this.resumen[0].valor = `${data.total_usuarios} registrados`;
      this.resumen[1].valor = `${data.especialistas} activos`;
      this.resumen[2].valor = `${data.pacientes} activos`;
      this.resumen[3].valor = `${data.citas_hoy} programadas`;
    });
  }
}
