import { Component, OnInit } from '@angular/core';
import { CitasService } from '../../services/Cita-Service/cita.service';
import { formatDate, CommonModule, DatePipe } from '@angular/common';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';


@Component({
  selector: 'app-medico',
  standalone: true,
  imports: [CommonModule, FormsModule],
  providers: [DatePipe],
  templateUrl: './medico.component.html',
  styleUrls: ['./medico.component.css']
})
export class MedicoComponent implements OnInit {

  medico: any = null;
  citas: any[] = [];
  fechaActual: Date;
  paginaActual: number = 1;
  citasPorPagina: number = 10;
  totalCitas: number = 0;

  constructor(private citasService: CitasService, private router: Router) {
    this.fechaActual = new Date();
  }

  ngOnInit(): void {
    this.obtenerMedico();
    const rol = localStorage.getItem('usuarioRol');
    if (rol !== 'medico') {
      console.error('Acceso no autorizado');
      this.router.navigate(['/login']);
      return;
    }

    
    this.cargarCitas();
  }

  obtenerMedico() {
    this.medico = JSON.parse(localStorage.getItem('usuario')!);
    if (!this.medico) {
      console.error('No se encontró información del médico');
      return;
    }
  }

  cargarCitas(): void {
    const fechaISO = formatDate(this.fechaActual, 'yyyy-MM-dd', 'en');
    this.citasService.obtenerCitasPorMedico(this.medico.id, fechaISO, this.paginaActual, this.citasPorPagina).subscribe(
        (response) => {
            if (!response || !response.data) {
                console.error('No se encontraron citas');
                return;
            }

            this.totalCitas = response.total;
            this.citas = response.data.slice(this.inicio(), this.fin());
            console.log('Citas cargadas', this.citas);
        },
        (error) => console.error('Error al obtener citas', error)
    );
}

  inicio(): number {
    return (this.paginaActual - 1) * this.citasPorPagina;
  }

  fin(): number {
    return this.paginaActual * this.citasPorPagina;
  }

  cambiarPagina(pagina: number): void {
    this.paginaActual = pagina;
    this.cargarCitas();
  }

  obtenerNumeroPaginas(): number[] {
    const totalPaginas = Math.ceil(this.totalCitas / this.citasPorPagina);
    return Array.from({ length: totalPaginas }, (_, i) => i + 1);
  }

  retrocederDia(): void {
    this.fechaActual.setDate(this.fechaActual.getDate() - 1);
    this.paginaActual = 1;
    this.cargarCitas();
  }

  avanzarDia(): void {
    this.fechaActual.setDate(this.fechaActual.getDate() + 1);
    this.paginaActual = 1;
    this.cargarCitas();
  }

  editarCita(citaId: number): void {
    // Navegación o lógica para editar
    console.log('Editar cita', citaId);
  }

  cambiarEstado(cita: any, nuevoEstado: string): void {
    cita.estado = nuevoEstado;
    this.citasService.actualizarEstadoCita(cita.id, nuevoEstado).subscribe(
      () => console.log('Estado actualizado'),
      (err) => console.error('Error al actualizar estado', err)
    );
  }
}
