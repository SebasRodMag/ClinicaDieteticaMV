import { Component, OnInit, ViewChild, TemplateRef, AfterViewInit } from '@angular/core';
import { Cita } from '../models/cita.model';
import { UserService } from '../service/User-Service/user.service';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { CommonModule } from '@angular/common';
import { MatSnackBar } from '@angular/material/snack-bar';
import { CitaPorPaciente } from '../models/citasPorPaciente.model';

@Component({
    selector: 'app-pacientes-citas',
    standalone: true,
    imports: [CommonModule, TablaDatosComponent],
    templateUrl: './pacientes-citas.component.html',
})
export class PacientesCitasComponent implements OnInit, AfterViewInit {
    citas: Cita[] = [];
    loading: boolean = false;
    columnas = ['id', 'fecha', 'hora', 'estado', 'nombre_especialista', 'accion'];

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
        this.UserService.obtenerCitasDelUsuarioAutenticado().subscribe({
            next: (data) => {
                this.citas = data;
                this.loading = false;
            },
            error: () => {
                this.loading = false;
                this.mostrarMensaje('Error al obtener las citas', 'error');
            },
        });
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
}
