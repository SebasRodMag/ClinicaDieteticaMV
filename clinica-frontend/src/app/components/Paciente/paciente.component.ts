import { Component, OnInit } from '@angular/core';
import { PacienteService } from '../../services/Paciente-Service/paciente.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';   


@Component({
    selector: 'app-paciente',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './paciente.component.html',
    styleUrls: ['./paciente.component.css'],
    })
export class PacienteComponent implements OnInit {
    pacientes: any[] = [];
    paginaActual: number = 1;
    pacientesPorPagina: number = 5;
    totalPacientes: number = 0;

    constructor(private pacienteService: PacienteService) {}

    ngOnInit(): void {
        this.cargarPacientes();
    }

    cargarPacientes(): void {
        this.pacienteService
        .obtenerPacientes(this.paginaActual, this.pacientesPorPagina)
        .subscribe({
            next: (response) => {
            this.pacientes = response.data;
            this.totalPacientes = response.total;
            },
            error: (error) => {
            console.error('Error al cargar pacientes', error);
            },
        });
    }

    cambiarPagina(nuevaPagina: number): void {
        this.paginaActual = nuevaPagina;
        this.cargarPacientes();
    }

    obtenerNumeroPaginas(): number[] {
        const totalPaginas = Math.ceil(this.totalPacientes / this.pacientesPorPagina);
        return Array.from({ length: totalPaginas }, (_, i) => i + 1);
    }

    verDetalles(pacienteId: number): void {
        console.log('Mostrar detalles del paciente con ID', pacienteId);
        // Puedes redirigir a otra ruta si es necesario:
        // this.router.navigate(['/paciente', pacienteId]);
    }

    editarPaciente(pacienteId: number): void {
        console.log('Editar paciente con ID', pacienteId);
        // Puedes redirigir a otra ruta si es necesario:
        // this.router.navigate(['/paciente/editar', pacienteId]);
    }
}
