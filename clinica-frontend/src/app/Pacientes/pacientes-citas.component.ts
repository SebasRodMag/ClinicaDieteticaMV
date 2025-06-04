import { Component, OnInit, ViewChild, TemplateRef, AfterViewInit } from '@angular/core';
import { Cita } from '../models/cita.model';
import { UserService } from '../service/User-Service/user.service';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { CommonModule } from '@angular/common';
import { MatSnackBar } from '@angular/material/snack-bar';
import { CitaPorPaciente } from '../models/citasPorPaciente.model';
import { FormsModule } from '@angular/forms';
import { Paciente } from '../models/paciente.model';
import {ModalNuevaCitaComponent}from './modal/modal-nueva-cita.component';

@Component({
    selector: 'app-pacientes-citas',
    standalone: true,
    imports: [CommonModule, TablaDatosComponent, FormsModule, ModalNuevaCitaComponent],
    templateUrl: './pacientes-citas.component.html',
})
export class PacientesCitasComponent implements OnInit, AfterViewInit {
    citaPorPaciente: CitaPorPaciente[] = [];
    citasFiltradas: CitaPorPaciente[] = [];
    modalVisible=false;
    filtro: string = '';
    loading: boolean = false;
    paciente!: Paciente;
    especialista!:any;

    paginaActual = 1;
    itemsPorPagina = 10;
    maxPaginasVisibles = 5;

    columnaOrden: string | null = null;
    direccionOrdenAsc: boolean = true;

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
                this.citaPorPaciente = data;
                this.filtrarCitas(); // aplicar filtro al cargar
                this.loading = false;
            },
            error: () => {
                this.loading = false;
                this.mostrarMensaje('Error al obtener las citas', 'error');
            },
        });
    }

    filtrarCitas(): void {
        const filtroLower = this.filtro.toLowerCase();
        this.citasFiltradas = this.citaPorPaciente.filter(cita =>
            cita.id.toString().includes(filtroLower) ||
            cita.fecha.toLowerCase().includes(filtroLower) ||
            cita.hora.toLowerCase().includes(filtroLower) ||
            cita.estado.toLowerCase().includes(filtroLower) ||
            cita.nombre_especialista.toLowerCase().includes(filtroLower)
        );
        this.ordenarDatos();
        this.paginaActual = 1;
    }

    ordenarDatos(): void {
        if (!this.columnaOrden) return;

        this.citasFiltradas.sort((a: any, b: any) => {
            const valA = a[this.columnaOrden!];
            const valB = b[this.columnaOrden!];

            if (valA < valB) return this.direccionOrdenAsc ? -1 : 1;
            if (valA > valB) return this.direccionOrdenAsc ? 1 : -1;
            return 0;
        });
    }

    ordenarPor(columna: string): void {
        if (this.columnaOrden === columna) {
            this.direccionOrdenAsc = !this.direccionOrdenAsc;
        } else {
            this.columnaOrden = columna;
            this.direccionOrdenAsc = true;
        }
        this.ordenarDatos();
    }

    cambiarPagina(pagina: number): void {
        this.paginaActual = pagina;
    }

    cancelarCita(cita: CitaPorPaciente): void {
        const snackRef = this.snackBar.open(
            `¿Cancelar la cita del ${cita.fecha} a las ${cita.hora}?`,
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
            this.UserService.cancelarCita(cita.id).subscribe({
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

    nuevaCita(): void {
        
    }
}
