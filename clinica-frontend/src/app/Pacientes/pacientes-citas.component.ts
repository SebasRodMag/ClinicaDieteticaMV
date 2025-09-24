import { Component, OnInit, OnDestroy, ViewChild, TemplateRef, AfterViewInit } from '@angular/core';
import { UserService } from '../service/User-Service/user.service';
import { AuthService } from '../service/Auth-Service/Auth.service';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { CommonModule } from '@angular/common';
import { MatSnackBar } from '@angular/material/snack-bar';
import { FormsModule } from '@angular/forms';
import { Paciente } from '../models/paciente.model';
import { ModalNuevaCitaComponent } from './modal/modal-nueva-cita.component';
import { Especialista } from '../models/especialista.model';
import { CitaPorEspecialista } from '../models/citasPorEspecialista.model';
import { CalendarioCitasComponent } from '../components/calendario/calendario-citas.component';
import { unirseConferencia } from '../components/utilidades/unirse-conferencia';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { CitaGenerica } from '../models/cita-generica.model';
import { ModalInfoCitaComponent } from '../components/calendario/modal/modal-info-cita.component';
import { mostrarBotonVideollamada } from '../components/utilidades/mostrar-boton-videollamada';

@Component({
    selector: 'app-pacientes-citas',
    standalone: true,
    imports: [
        CommonModule,
        TablaDatosComponent,
        FormsModule,
        ModalNuevaCitaComponent,
        CalendarioCitasComponent,
        ModalInfoCitaComponent
    ],
    templateUrl: './pacientes-citas.component.html',
})
export class PacientesCitasComponent implements OnInit, AfterViewInit, OnDestroy {
    citas: CitaGenerica[] = [];
    citasFiltradas: CitaGenerica[] = [];

    filtroTexto: string = '';
    temporizadorActualizacion: any;

    modalVisible = false;
    loading: boolean = false;
    huboError: boolean = false;

    paciente!: Paciente;
    especialista!: Especialista | null;

    paginaActual = 1;
    itemsPorPagina = 10;
    maxPaginasVisibles = 5;

    columnaOrden: string | null = null;
    direccionOrdenAsc: boolean = true;

    citaSeleccionada: CitaGenerica | null = null;
    modalInfoCitaVisible = false;

    columnas = ['id', 'fecha', 'hora', 'estado', 'nombre_especialista', 'especialidad', 'tipo_cita', 'accion'];

    @ViewChild('accionTemplate', { static: true }) accionTemplate!: TemplateRef<any>;
    templatesMap: { [key: string]: TemplateRef<any> } = {};

    permitirCrearCitaPaciente: boolean = false;

    constructor(
        private UserService: UserService,
        private authService: AuthService,
        private snackBar: MatSnackBar,
        private HttpClient: HttpClient
    ) { }

    ngOnInit(): void {
        this.obtenerCitas();
        this.cargarConfiguracion();
        this.iniciarActualizacionPeriodica();
    }

    ngAfterViewInit(): void {
        this.templatesMap = { accion: this.accionTemplate };
    }

    ngOnDestroy(): void {
        if (this.temporizadorActualizacion) clearInterval(this.temporizadorActualizacion);
    }

    cargarConfiguracion(): void {
        this.UserService.getConfiguracion().subscribe({
            next: (res: { message: string; configuraciones?: Record<string, any> }) => {
                const respuesta = res?.configuraciones?.['Crear_cita_paciente'];
                this.permitirCrearCitaPaciente = respuesta;

                console.log('Crear_cita_paciente ->', respuesta);
            },
            error: () => {
                this.permitirCrearCitaPaciente = false;
                this.snackBar.open('Error al cargar configuración', 'Cerrar', { duration: 3000 });
            }
        });
    }

    obtenerCitas(): void {
        this.loading = true;
        this.huboError = false;
        this.UserService.obtenerCitasDelPacienteAutenticado().subscribe({
            next: (data) => {
                this.citas = data.citas;
                this.filtrarCitas();
                this.loading = false;
            },
            error: () => {
                this.loading = false;
                this.huboError = true;
                this.mostrarMensaje('Error al obtener las citas', 'error');
            },
        });
    }

    filtrarCitas(): void {
        const filtroLower = this.filtroTexto.toLowerCase();

        this.citasFiltradas = this.citas.filter(cita => {
            if (this.esCitaPorEspecialista(cita)) {
                return (
                    cita.nombre_especialista.toLowerCase().includes(filtroLower) ||
                    cita.especialidad.toLowerCase().includes(filtroLower) ||
                    cita.estado.toLowerCase().includes(filtroLower)
                );
            }
            return cita.estado.toLowerCase().includes(filtroLower);
        });

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

    cancelarCita(cita: CitaGenerica): void {
        if (!this.esCitaPorEspecialista(cita)) return;

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
        this.modalVisible = true;
    }

    cancelarCitaDesdeCalendario(idCita: number): void {
        const cita = this.citas.find(c => c.id === idCita);
        if (!cita) return;

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

    // Acepta CitaGenerica (solo usa id)
    unirseAVideollamada(cita: CitaGenerica): void {
        const url = environment.apiBase;
        unirseConferencia(cita.id, this.HttpClient, this.snackBar, url);
    }

    puedeUnirseACita(cita: CitaGenerica): boolean {
        return mostrarBotonVideollamada(cita, {
            minutosAntes: 5,
            minutosDespues: 30,
            requiereSala: false
        });
    }

    iniciarActualizacionPeriodica(): void {
        this.temporizadorActualizacion = setInterval(() => {
            this.filtrarCitas();
        }, 15000);
    }

    esCitaPorEspecialista(cita: CitaGenerica): cita is CitaPorEspecialista {
        return 'nombre_especialista' in cita && 'especialidad' in cita;
    }

    abrirModalInfoCita(cita: CitaGenerica): void {
        this.citaSeleccionada = { ...cita };
        this.modalInfoCitaVisible = true;
    }
}
