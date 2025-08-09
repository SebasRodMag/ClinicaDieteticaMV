import { Component, OnInit, ViewChild, TemplateRef, AfterViewInit } from '@angular/core';
import { UserService } from '../service/User-Service/user.service';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { CommonModule } from '@angular/common';
import { MatSnackBar } from '@angular/material/snack-bar';
import { CitaPorPaciente } from '../models/citasPorPaciente.model';
import { FormsModule } from '@angular/forms';
import { ModalNuevaCitaComponent } from './modal/modal-nueva-cita.component';
import { unirseConferencia } from '../components/utilidades/unirse-conferencia';
import { HttpClient } from '@angular/common/http';
import { urlApiServicio } from '../components/utilidades/variable-entorno';
import { mostrarBotonVideollamada } from '../components/utilidades/mostrar-boton-videollamada';
import { formatearFecha } from '../components/utilidades/sanitizar.utils';
import { CalendarioCitasComponent } from '../components/calendario/calendario-citas.component';
import { ModalInfoCitaComponent } from '../components/calendario/modal/modal-info-cita.component';
import { CitaGenerica } from '../models/cita-generica.model';
import { CitaPorEspecialista } from '../models/citasPorEspecialista.model';
import { HistorialService } from '../service/Historial-Service/historial.service';
import { Historial } from '../models/historial.model';
import { ModalEditHistorialComponent } from './modal/modal-edit-historial.component';

@Component({
    selector: 'app-especialista-citas',
    standalone: true,
    imports: [CommonModule, TablaDatosComponent, FormsModule, ModalNuevaCitaComponent, CalendarioCitasComponent, ModalInfoCitaComponent, ModalEditHistorialComponent],
    templateUrl: './especialista-citas.component.html',
})
export class EspecialistaCitasComponent implements OnInit, AfterViewInit {
    citas: CitaPorPaciente[] = [];
    citasFiltradas: CitaPorPaciente[] = [];
    citaSeleccionada: CitaGenerica | null = null;
    citasGenericas: CitaGenerica[] = [];
    especialistaId: number | null = null;
    formatearFecha = formatearFecha;

    filtroTexto: string = '';

    loading: boolean = false;
    huboError: boolean = false;
    modalNuevaCitaVisible: boolean = false;
    modalInfoCitaVisible = false;
    cargandoActualizarEstado: boolean = false;
    modalHistorialVisible = false;
    borradorHistorial: Partial<Historial> = {};
    mostrarBotonVideollamada = mostrarBotonVideollamada;

    columnas = ['id', 'fecha', 'hora', 'nombre_paciente', 'dni_paciente', 'estado', 'tipo_cita', 'accion'];

    @ViewChild('accionTemplate', { static: true }) accionTemplate!: TemplateRef<any>;
    @ViewChild('fechaTemplate', { static: true }) fechaTemplate!: TemplateRef<any>;
    @ViewChild('horaTemplate', { static: true }) horaTemplate!: TemplateRef<any>;

    templatesMap: { [key: string]: TemplateRef<any> } = {};

    constructor(private historialService: HistorialService, private UserService: UserService, private snackBar: MatSnackBar, private HttpClient: HttpClient) { }

    ngOnInit(): void {
        this.obtenerCitas();
        this.cargarEspecialistaId();
    }

    ngAfterViewInit(): void {
        this.templatesMap = {
            accion: this.accionTemplate,
            fecha: this.fechaTemplate,
            hora: this.horaTemplate,
        };
    }

    cargarEspecialistaId(): void {
        this.UserService.obtenerPerfilEspecialista().subscribe({
            next: (perfil) => this.especialistaId = perfil?.id ?? null,
            error: () => this.especialistaId = null
        });
    }

    obtenerCitas(): void {
        this.loading = true;
        this.huboError = false;

        this.UserService.obtenerCitasDelEspecialistaAutenticado().subscribe({
            next: (response) => {
                if (Array.isArray(response.citas)) {
                    this.citas = response.citas;
                    this.citasGenericas = this.citas;
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

        this.citasFiltradas = this.citas.filter((cita) =>
            cita.nombre_paciente.toLowerCase().includes(termino) ||
            cita.dni_paciente.toLowerCase().includes(termino) ||
            cita.estado.toLowerCase().includes(termino) ||
            cita.tipo_cita.toLowerCase().includes(termino)
        );
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

    nuevaCita(): void {
        this.modalNuevaCitaVisible = true;
    }

    cuandoCitaCreada(): void {
        this.modalNuevaCitaVisible = false;
        this.obtenerCitas();
    }

    unirseAVideollamada(cita: CitaGenerica): void {
        const url = urlApiServicio.apiUrl;
        unirseConferencia(cita.id, this.HttpClient, this.snackBar, url);
    }

    abrirModalInfoCita(cita: CitaGenerica): void {
        this.citaSeleccionada = { ...cita };
        this.modalInfoCitaVisible = true;
    }

    cerrarModalCita(): void {
        this.modalInfoCitaVisible = false;
        this.citaSeleccionada = null;
    }

    cancelarCitaDesdeCalendario(idCita: number): void {
        const cita = this.citas.find(c => c.id === idCita);
        if (!cita) return;
        this.cancelarCita(cita);
    }

    actualizarEstadoCita(evento: { id: number; nuevoEstado: string }): void {
        this.cargandoActualizarEstado = true;

        this.UserService.cambiarEstadoCita(evento.id, evento.nuevoEstado).subscribe({
            next: () => {
                this.obtenerCitas();
                this.modalInfoCitaVisible = false;
                this.snackBar.open('Estado actualizado correctamente.', 'Cerrar', { duration: 3000 });
            },
            error: () => {
                this.cargandoActualizarEstado = false;
                this.snackBar.open('No se pudo actualizar el estado de la cita.', 'Cerrar', { duration: 3000 });
            },
            complete: () => {
                this.cargandoActualizarEstado = false;
            }
        });
    }

    abrirHistorialParaCita(evt: { id_paciente: number; id_cita: number; fecha: string }) {
        this.historialService.obtenerUltimoHistorialPorPaciente(evt.id_paciente).subscribe({
            next: (ultimo) => {
                this.borradorHistorial = {
                    id_paciente: evt.id_paciente,
                    fecha: evt.fecha, // hoy
                    observaciones_especialista: ultimo?.observaciones_especialista ?? '',
                    recomendaciones: '',
                    dieta: '',
                    lista_compra: '',
                    id_especialista: this.especialistaId ?? undefined
                };
                this.modalHistorialVisible = true;
            },
            error: () => {
                this.borradorHistorial = {
                    id_paciente: evt.id_paciente,
                    fecha: evt.fecha,
                    observaciones_especialista: '',
                    id_especialista: this.especialistaId ?? undefined
                };
                this.modalHistorialVisible = true;
                this.snackBar.open('No se pudieron cargar observaciones previas', 'Cerrar', { duration: 3000 });
            }
        });
    }

    guardarHistorial(payload: Partial<Historial>) {
        this.historialService.crearHistorial(payload).subscribe({
            next: () => {
                this.snackBar.open('Historial guardado', 'Cerrar', { duration: 2500 });
                this.modalHistorialVisible = false;
            },
            error: () => this.snackBar.open('Error al guardar historial', 'Cerrar', { duration: 3000 })
        });
    }
}
