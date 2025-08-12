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
import { HistorialService } from '../service/Historial-Service/historial.service';
import { Historial } from '../models/historial.model';
import { ModalEditHistorialComponent } from './modal/modal-edit-historial.component';
import { CitaGenericaExtendida } from '../models/cita-generica.model';

@Component({
    selector: 'app-especialista-citas',
    standalone: true,
    imports: [
        CommonModule,
        TablaDatosComponent,
        FormsModule,
        ModalNuevaCitaComponent,
        CalendarioCitasComponent,
        ModalInfoCitaComponent,
        ModalEditHistorialComponent
    ],
    templateUrl: './especialista-citas.component.html',
})
export class EspecialistaCitasComponent implements OnInit, AfterViewInit {
    citas: CitaPorPaciente[] = [];
    citasFiltradas: CitaPorPaciente[] = [];
    citaSeleccionada: CitaGenericaExtendida | null = null;
    citasGenericas: CitaGenerica[] = [];
    especialistaId: number | null = null;
    pacienteNombre: string = '';
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

    constructor(
        private historialService: HistorialService,
        private UserService: UserService,
        private snackBar: MatSnackBar,
        private HttpClient: HttpClient
    ) { }

    private normalizar(s: string): string {
        return (s || '').toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').trim();
    }

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
        const original: any = this.citas.find(c => c.id === cita.id);
        const id_paciente =
            (original?.id_paciente ?? original?.paciente_id ?? original?.paciente?.id) ?? null;

        this.citaSeleccionada = id_paciente
            ? { ...cita, id_paciente }
            : { ...cita };

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

    abrirHistorialParaCita(evt: {
        id_paciente: number | null;
        id_cita: number;
        fecha: string;
        nombre_paciente?: string;
        dni_paciente?: string;
    }) {
        this.pacienteNombre = evt.nombre_paciente ?? '';

        // 1) cierra el modal-info
        this.modalInfoCitaVisible = false;

        // 2) espera un tick para que Angular quite el DOM y backdrop, y LUEGO sigue
        setTimeout(() => {
            const continuarCon = (idPaciente: number | null) => {
                if (idPaciente) {
                    this.historialService.obtenerUltimoHistorialPorPaciente(idPaciente).subscribe({
                        next: (ultimo) => {
                            this.borradorHistorial = {
                                id_paciente: idPaciente,
                                fecha: evt.fecha,
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
                                id_paciente: idPaciente,
                                fecha: evt.fecha,
                                observaciones_especialista: '',
                                id_especialista: this.especialistaId ?? undefined
                            };
                            this.modalHistorialVisible = true;
                            this.snackBar.open('No se pudieron cargar observaciones previas', 'Cerrar', { duration: 3000 });
                        }
                    });
                    return;
                }

                // Sin id: abre y que el ng-select preseleccione por nombre
                this.borradorHistorial = {
                    fecha: evt.fecha,
                    observaciones_especialista: '',
                    id_especialista: this.especialistaId ?? undefined
                };
                this.modalHistorialVisible = true;
            };

            if (evt.id_paciente) {
                continuarCon(evt.id_paciente);
                return;
            }

            // Fallback: resolver id por nombre/DNI
            this.historialService.obtenerPacientesEspecialista().subscribe({
                next: (lista: any[]) => {
                    const targetNombre = this.normalizar(evt.nombre_paciente || '');
                    const targetDni = this.normalizar(evt.dni_paciente || '');
                    const encontrado = (lista || []).find((p: any) => {
                        const nombreCompleto = this.normalizar(`${p.nombre} ${p.apellidos}`);
                        const dni = this.normalizar(p.dni || '');
                        return (targetDni && dni === targetDni) || (!!targetNombre && nombreCompleto === targetNombre);
                    });
                    continuarCon(encontrado?.id ?? null);
                },
                error: () => continuarCon(null)
            });
        }, 0);
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

    cancelarCitaDesdeModal(idCita: number): void {
        const cita = this.citas.find(c => c.id === idCita);
        if (!cita) {
            this.mostrarMensaje('No se encontró la cita seleccionada.', 'error');
            return;
        }
        this.cancelarCita(cita);
    }
}