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
import { environment } from '../../environments/environment';
import { mostrarBotonVideollamada } from '../components/utilidades/mostrar-boton-videollamada';
import { formatearFecha } from '../components/utilidades/sanitizar.utils';
import { CalendarioCitasComponent } from '../components/calendario/calendario-citas.component';
import { ModalInfoCitaComponent } from '../components/calendario/modal/modal-info-cita.component';
import { CitaGenerica } from '../models/cita-generica.model';
import { HistorialService } from '../service/Historial-Service/historial.service';
import { Historial } from '../models/historial.model';
import { ModalEditHistorialComponent } from './modal/modal-edit-historial.component';
import { CitaGenericaExtendida } from '../models/cita-generica.model';
import { forkJoin, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';

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
        ModalEditHistorialComponent,
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
    cargandoModalHistorial = false;
    pacientesPrecargados: any[] = [];
    formatearFecha = formatearFecha;

    listaPacientesParaModal: Array<{ id: number; nombreCompleto: string }> = [];
    historialSeleccionado: Partial<Historial> = {};
    modalVisible = false;
    esNuevo = true;
    filtroTexto: string = '';

    paginaActual = 1;
    itemsPorPagina = 10;
    maxPaginasVisibles = 5;

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
        const termino = (this.filtroTexto || '').toLowerCase().trim();

        if (!termino) {
            this.citasFiltradas = [...this.citas];
        } else {
            this.citasFiltradas = this.citas.filter(c =>
                (c.nombre_paciente || '').toLowerCase().includes(termino) ||
                (c.dni_paciente || '').toLowerCase().includes(termino) ||
                (c.estado || '').toLowerCase().includes(termino) ||
                (c.tipo_cita || '').toLowerCase().includes(termino)
            );
        }

        this.paginaActual = 1;
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
        const url = environment.apiBase;
        unirseConferencia(cita.id, this.HttpClient, this.snackBar, url);
    }

    unirseAVideollamadaConHistorial(cita: CitaGenerica): void {
        //cogemos los datos para pasar al modal
        const original: any = this.citas.find(c => c.id === cita.id);
        const id_paciente =
            (original?.id_paciente ?? original?.paciente_id ?? original?.paciente?.id) ?? null;

        const hoy = new Date();
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd = String(hoy.getDate()).padStart(2, '0');

        // Reutilizamos el mismo flujo de "Ir a la cita"
        this.abrirHistorialParaCita({
            id_paciente,
            id_cita: cita.id,
            fecha: `${yyyy}-${mm}-${dd}`,
            nombre_paciente: (cita as any).nombre_paciente || '',
            dni_paciente: (cita as any).dni_paciente || ''
        });

        // Le damos un diley a la apertura de la videollamada  para que se vea el spinner primero
        setTimeout(() => {
            const url = environment.apiBase;
            unirseConferencia(cita.id, this.HttpClient, this.snackBar, url);
        }, 0);
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

    cerrarModalInfo(): void {
        this.modalInfoCitaVisible = false;
        this.citaSeleccionada = null;
    }

    cancelarCitaDesdeCalendario(idCita: number): void {
        const cita = this.citas.find(c => c.id === idCita);
        if (!cita) return;
        this.cancelarCita(cita);
    }

    actualizarEstadoCita(evento: { id: number; nuevoEstado: string; motivo?: string }): void {
        this.cargandoActualizarEstado = true;

        if (evento.nuevoEstado === 'cancelada') {
            this.UserService.cancelarCita(evento.id, evento.motivo).subscribe({
                next: () => {
                    this.obtenerCitas();
                    this.modalInfoCitaVisible = false;
                    this.snackBar.open('Cita cancelada correctamente.', 'Cerrar', { duration: 3000 });
                },
                error: () => {
                    this.cargandoActualizarEstado = false;
                    this.snackBar.open('No se pudo cancelar la cita.', 'Cerrar', { duration: 3000 });
                },
                complete: () => this.cargandoActualizarEstado = false
            });
            return;
        }

        // Otros estados -> cambiarEstadoCita
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
            complete: () => this.cargandoActualizarEstado = false
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
        this.modalInfoCitaVisible = false;
        this.cargandoModalHistorial = true;

        const normalizar = (s: string) =>
            (s || '').toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '').trim();
        const pacientes$ = this.historialService.obtenerPacientesEspecialista().pipe(
            map((lista: any[]) => lista.map(p => ({
                ...p,
                nombreCompleto: `${p.nombre} ${p.apellidos}`.trim()
            })))
        );

        const idInicial = evt.id_paciente ?? null;
        const ultimo$ = idInicial
            ? this.historialService.obtenerUltimoHistorialPorPaciente(idInicial).pipe(catchError(() => of(null)))
            : of(null);

        forkJoin({ pacientes: pacientes$, ultimo: ultimo$ }).subscribe({
            next: ({ pacientes, ultimo }) => {
                let idPaciente = idInicial;
                if (!idPaciente) {
                    const targetNombre = normalizar(evt.nombre_paciente || '');
                    const targetDni = normalizar(evt.dni_paciente || '');
                    const encontrado = (pacientes || []).find((p: any) => {
                        const nombreCompleto = normalizar(`${p.nombre} ${p.apellidos}`);
                        const dni = normalizar(p.dni || '');
                        return (targetDni && dni === targetDni) || (!!targetNombre && nombreCompleto === targetNombre);
                    });
                    idPaciente = encontrado?.id ?? null;
                }
                const encontradoEnLista = (pacientes || []).find((p: any) => Number(p.id) === Number(idPaciente));
                const nombreCompleto = encontradoEnLista
                    ? `${encontradoEnLista.nombre} ${encontradoEnLista.apellidos}`.trim()
                    : (evt.nombre_paciente || 'Paciente');

                this.borradorHistorial = {
                    id_paciente: idPaciente ?? undefined,
                    fecha: evt.fecha,
                    observaciones_especialista: ultimo?.observaciones_especialista ?? '',
                    recomendaciones: '',
                    dieta: '',
                    lista_compra: '',
                    id_especialista: this.especialistaId ?? undefined
                };
                this.listaPacientesParaModal = idPaciente ? [{ id: Number(idPaciente), nombreCompleto }] : [];

                this.historialSeleccionado = { ...this.borradorHistorial };

                this.pacientesPrecargados = pacientes;
                this.modalHistorialVisible = true;
                this.cargandoModalHistorial = false;
            },
            error: () => {
                this.cargandoModalHistorial = false;
                this.snackBar.open('No se pudieron cargar los datos del historial.', 'Cerrar', { duration: 3000 });
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

    cancelarCitaDesdeModal(idCita: number): void {
        const cita = this.citas.find(c => c.id === idCita);
        if (!cita) {
            this.mostrarMensaje('No se encontró la cita seleccionada.', 'error');
            return;
        }
        this.cancelarCita(cita);
    }

    abrirEdicionDesdeCalendario(payload: {
        id_paciente: number | null;
        id_cita: number;
        fecha: string;
        nombre_paciente?: string;
        dni_paciente?: string;
    }) {
        const id = payload.id_paciente != null ? Number(payload.id_paciente) : null;
        const nombre = (payload.nombre_paciente || '').trim();
        this.historialSeleccionado = {
            id_paciente: id ?? undefined, fecha: payload.fecha,
        };
        this.listaPacientesParaModal = id ? [{ id, nombreCompleto: nombre || 'Paciente' }] : [];
        this.esNuevo = true;
        this.modalHistorialVisible = true;
    }

    //Para facilitar el nombre del paciente en el modal ya que varia dependiendo desde donde se accede al mismo
    get pacienteNombreParaModal(): string {
        return this.listaPacientesParaModal[0]?.nombreCompleto || this.pacienteNombre || '';
    }
}