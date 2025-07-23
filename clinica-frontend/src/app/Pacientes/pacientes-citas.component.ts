import { Component, OnInit, OnDestroy, ViewChild, TemplateRef, AfterViewInit } from '@angular/core';
import { UserService } from '../service/User-Service/user.service';
import { AuthService } from '../service/Auth-Service/Auth.service';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { CommonModule } from '@angular/common';
import { MatSnackBar } from '@angular/material/snack-bar';
import { CitaPorPaciente } from '../models/citasPorPaciente.model';
import { FormsModule } from '@angular/forms';
import { Paciente } from '../models/paciente.model';
import { ModalNuevaCitaComponent } from './modal/modal-nueva-cita.component';
import { Especialista } from '../models/especialista.model';
import { CitaPorEspecialista } from '../models/citasPorEspecialista.model';
import { CalendarioCitasComponent } from './calendario/calendario-citas.component';
import { unirseConferencia } from '../components/utilidades/unirse-conferencia';
import { HttpClient } from '@angular/common/http';
import { urlApiServicio } from '../components/utilidades/variable-entorno';



@Component({
    selector: 'app-pacientes-citas',
    standalone: true,
    imports: [CommonModule, TablaDatosComponent, FormsModule, ModalNuevaCitaComponent, CalendarioCitasComponent],
    templateUrl: './pacientes-citas.component.html',
})
export class PacientesCitasComponent implements OnInit, AfterViewInit, OnDestroy {
    citas: CitaPorEspecialista[] = [];
    citasFiltradas: CitaPorEspecialista[] = [];

    filtroTexto: string = '';
    temporizadorActualizacion: any;//Para actualizar el botón de unirse a la conferencia sin recargar la página

    modalVisible = false;
    loading: boolean = false;
    huboError: boolean = false;

    paciente!: Paciente;
    especialista!: any;

    paginaActual = 1;
    itemsPorPagina = 10;
    maxPaginasVisibles = 5;

    columnaOrden: string | null = null;
    direccionOrdenAsc: boolean = true;

    columnas = ['id', 'fecha', 'hora', 'estado', 'nombre_especialista', 'especialidad', 'tipo_cita', 'accion'];

    @ViewChild('accionTemplate', { static: true }) accionTemplate!: TemplateRef<any>;

    templatesMap: { [key: string]: TemplateRef<any> } = {};

    permitirCrearCitaPaciente: boolean = false;

    constructor(private UserService: UserService, private authService: AuthService, private snackBar: MatSnackBar, private HttpClient: HttpClient) { }

    ngOnInit(): void {
        this.obtenerCitas();
        this.cargarConfiguracion();
        this.iniciarActualizacionPeriodica();
    }

    ngAfterViewInit(): void {
        this.templatesMap = {
            accion: this.accionTemplate,
        };
    }

    ngOnDestroy(): void {
        if (this.temporizadorActualizacion) {
            clearInterval(this.temporizadorActualizacion);
        }
    }

    cargarConfiguracion(): void {
        this.UserService.getConfiguracion().subscribe({
            next: (config: any) => {
                this.permitirCrearCitaPaciente = config?.['Crear_cita_paciente'] === 'true';
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

        this.citasFiltradas = this.citas.filter(cita =>
            cita.nombre_especialista.toLowerCase().includes(filtroLower) ||
            cita.especialidad.toLowerCase().includes(filtroLower) ||
            cita.estado.toLowerCase().includes(filtroLower)
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

    cancelarCita(cita: CitaPorEspecialista): void {
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

    onCambioTexto(): void {
        this.filtrarCitas();
    }

    onCambioFecha(): void {
        this.filtrarCitas();
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

    unirseAVideollamada(cita: CitaPorEspecialista): void {
        const url = urlApiServicio.apiUrl;
        unirseConferencia(cita.id, this.HttpClient, this.snackBar, url);
    }

    puedeUnirseACita(cita: CitaPorEspecialista): boolean {
        if (cita.tipo_cita !== 'telemática') return false;

        const ahora = new Date();
        const fechaHoraCita = new Date(`${cita.fecha}T${cita.hora}`);

        //Tiempo durante el cual se muestra el botón de Unirse
        const cincoMinAntes = new Date(fechaHoraCita.getTime() - 5 * 60 * 1000);//5 minutos antes de la cita
        const treintaMinDespues = new Date(fechaHoraCita.getTime() + 30 * 60 * 1000);//30 minutos después del inicio de la cita

        return ahora >= cincoMinAntes && ahora <= treintaMinDespues;
    }

    iniciarActualizacionPeriodica(): void {
        this.temporizadorActualizacion = setInterval(() => {
            this.filtrarCitas(); //actualiza los datos y reevaluará `puedeUnirseACita` por cada cita visible
        }, 15000); //cada 15 segundos
    }


}
