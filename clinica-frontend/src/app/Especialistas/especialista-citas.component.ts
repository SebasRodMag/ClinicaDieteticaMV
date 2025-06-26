import { Component, OnInit, ViewChild, TemplateRef, AfterViewInit } from '@angular/core';
import { UserService } from '../service/User-Service/user.service';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { CommonModule } from '@angular/common';
import { MatSnackBar } from '@angular/material/snack-bar';
import { CitaPorPaciente } from '../models/citasPorPaciente.model';
import { FormsModule } from '@angular/forms';

@Component({
    selector: 'app-especialista-citas',
    standalone: true,
    imports: [CommonModule, TablaDatosComponent, FormsModule],
    templateUrl: './especialista-citas.component.html',
})
export class EspecialistaCitasComponent implements OnInit, AfterViewInit {
    citas: CitaPorPaciente[] = [];
    citasFiltradas: CitaPorPaciente[] = [];

    filtroTexto: string = '';
    filtroFecha: string = new Date().toISOString().split('T')[0]; // YYYY-MM-DD

    loading: boolean = false;
    huboError: boolean = false;

    columnas = ['id', 'fecha', 'hora', 'nombre_paciente', 'dni_paciente', 'estado', 'tipo_cita', 'accion'];

    @ViewChild('accionTemplate', { static: true }) accionTemplate!: TemplateRef<any>;

    templatesMap: { [key: string]: TemplateRef<any> } = {};

    constructor(private UserService: UserService, private snackBar: MatSnackBar) { }

    ngOnInit(): void {
        this.obtenerCitas();
    }

    ngAfterViewInit(): void {
        this.templatesMap = {
            accion: this.accionTemplate,
        };
    }

    obtenerCitas(): void {
        this.loading = true;
        this.huboError = false;

        this.UserService.obtenerCitasDelEspecialistaAutenticado().subscribe({
            next: (response) => {
                if (Array.isArray(response.citas)) {
                    this.citas = response.citas;
                    this.aplicarFiltros();
                } else {
                    this.citas = [];
                    console.warn('La respuesta no contiene un array de citas:', response);
                }
                this.loading = false;
            },
            error: () => {
                this.loading = false;
                this.huboError = true;
                this.mostrarMensaje('Error al obtener las citas', 'error');
            },
        });
    }

    aplicarFiltros(): void {
        const termino = this.filtroTexto.toLowerCase().trim();
        const fechaSeleccionada = this.filtroFecha;

        this.citasFiltradas = this.citas.filter((cita) =>
            cita.fecha === fechaSeleccionada &&
            (
                cita.nombre_paciente.toLowerCase().includes(termino) ||
                cita.dni_paciente.toLowerCase().includes(termino) ||
                cita.estado.toLowerCase().includes(termino) ||
                cita.tipo_cita.toLowerCase().includes(termino)
            )
        );
    }

    onCambioTexto(): void {
        this.aplicarFiltros();
    }

    onCambioFecha(): void {
        this.aplicarFiltros();
    }

    cancelarCita(CitaPorPaciente: CitaPorPaciente): void {
        const snackRef = this.snackBar.open(
            `¿Cancelar la cita del ${CitaPorPaciente.fecha} a las ${CitaPorPaciente.hora}?`,
            'Cancelar',
            {
                duration: 5000,
                panelClass: ['snackbar-delete'],
                horizontalPosition: 'center',
                verticalPosition: 'top',
            }
        );

        snackRef.onAction().subscribe(() => {
            this.loading = true;
            this.UserService.cancelarCita(CitaPorPaciente.id).subscribe({
                next: () => {
                    this.obtenerCitas();
                    this.mostrarMensaje('Cita cancelada correctamente.', 'success');
                },
                error: () => {
                    this.loading = false;
                    this.mostrarMensaje('Error al cancelar la cita.', 'error');
                },
            });
        });
    }

    mostrarMensaje(mensaje: string, tipo: 'success' | 'error') {
        this.snackBar.open(mensaje, 'Cerrar', {
            duration: 3000,
            panelClass: tipo === 'success' ? ['snackbar-' + tipo] : undefined,
        });
    }

    cambiarDia(dias: number): void {
        const fecha = new Date(this.filtroFecha);
        fecha.setDate(fecha.getDate() + dias);
        this.filtroFecha = fecha.toISOString().split('T')[0];
        this.aplicarFiltros();
    }
}
