import { Component, Input, Output, EventEmitter, OnInit, OnChanges, OnDestroy, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CitaGenerica } from '../../../models/cita-generica.model';
import { FormsModule } from '@angular/forms';
import { UserService } from '../../../service/User-Service/user.service';
import { MatSnackBar } from '@angular/material/snack-bar';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';
import { unirseConferencia } from '../../utilidades/unirse-conferencia';
import { construirFechaHoraLocal } from '../../utilidades/sanitizar.utils';
import { mostrarBotonVideollamada } from '../../utilidades/mostrar-boton-videollamada';
import { CitaGenericaExtendida } from '../../../models/cita-generica.model';

@Component({
    selector: 'app-modal-info-cita',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './modal-info-cita.component.html',
})
export class ModalInfoCitaComponent implements OnInit, OnChanges, OnDestroy {
    @Input() citaSeleccionada: CitaGenericaExtendida | null = null;
    @Input() color: string = '#b7bbc2ff';  //<-----Color por defecto #b7bbc2ff
    @Input() esEspecialista: boolean = false;

    @Output() cerrado = new EventEmitter<void>();
    @Output() cancelar = new EventEmitter<number>();
    @Output() estadoActualizado = new EventEmitter<{ id: number; nuevoEstado: string }>();
    @Output() irACita = new EventEmitter<{ id_paciente: number | null; id_cita: number; fecha: string; nombre_paciente?: string; dni_paciente?: string; }>();

    puedeCancelar: boolean = false;
    mostrarUnirse = false;
    mostrarIrAPresencial = false;
    nuevoEstado: string = '';
    mensajeEstadoActualizado: string = '';
    tiposEstado: string[] = [];
    cargando: boolean = true;
    listaPacientesParaModal: Array<{ id: number; nombreCompleto: string }> = [];

    private temporizadorUi: any;

    constructor(private http: HttpClient, private userService: UserService, private snackBar: MatSnackBar) { }

    ngOnInit(): void {
        if (this.citaSeleccionada) {
            this.recalcularBotones();
        }
        this.cargarTiposEstado();
        this.temporizadorUi = setInterval(() => {
            this.recalcularBotones();
        }, 15000);//recalcula cada 15 segundos
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['citaSeleccionada'] && this.citaSeleccionada) {
            this.recalcularBotones();
        }
    }

    ngOnDestroy(): void {
        if (this.temporizadorUi) clearInterval(this.temporizadorUi);
    }

    cerrarModal(): void {
        if (this.temporizadorUi) {
            clearInterval(this.temporizadorUi);
            this.temporizadorUi = null;
        }
        this.cerrado.emit();
    }

    cancelarCita(): void {
        const cita = this.citaSeleccionada;
        if (!cita) return;

        const mensaje = `¿Cancelar la cita del ${cita.fecha} a las ${cita.hora}?`;

        const snackRef = this.snackBar.open(mensaje, 'Cancelar cita', {
            duration: 5000,
            panelClass: ['snackbar-delete'],
            horizontalPosition: 'center',
            verticalPosition: 'top',
        });

        snackRef.onAction().subscribe(() => {
            if (cita.id) this.cancelar.emit(cita.id);
        });
    }

    evaluarCancelacion(): void {
        if (!this.citaSeleccionada || this.citaSeleccionada.estado !== 'pendiente') {
            this.puedeCancelar = false;
            return;
        }

        const fechaHoraCita = construirFechaHoraLocal(
            this.citaSeleccionada.fecha,
            this.citaSeleccionada.hora
        );

        if (isNaN(fechaHoraCita.getTime())) {
            console.warn('Fecha/hora de cita inválida:', this.citaSeleccionada);
            this.puedeCancelar = false;
            return;
        }

        const ahora = new Date();
        const diffHoras = (fechaHoraCita.getTime() - ahora.getTime()) / (1000 * 60 * 60);
        this.puedeCancelar = diffHoras > 24;
    }

    tienePropiedad(prop: string): boolean {
        if (!this.citaSeleccionada) return false;
        return Object.prototype.hasOwnProperty.call(this.citaSeleccionada, prop);
    }

    obtenerPropiedad(prop: string): string {
        return this.citaSeleccionada && (this.citaSeleccionada as any)[prop]
            ? String((this.citaSeleccionada as any)[prop])
            : '';
    }

    actualizarEstado(): void {
        if (!this.nuevoEstado || !this.citaSeleccionada) return;

        if (this.nuevoEstado === 'cancelada' && !this.puedeCancelar) {
            this.snackBar.open('No puedes cancelar una cita con menos de 24 horas de antelación.', 'Cerrar', { duration: 3000 });
            return;
        }

        this.estadoActualizado.emit({ id: this.citaSeleccionada.id, nuevoEstado: this.nuevoEstado });
        this.mensajeEstadoActualizado = `Estado actualizado a "${this.nuevoEstado}".`;
    }

    cargarTiposEstado(): void {
        this.userService.getTiposEstadoCita().subscribe({
            next: (respuesta) => {
                if (respuesta.success) this.tiposEstado = respuesta.tipos_estado;
                this.cargando = false;
            },
            error: () => {
                console.warn('No se pudieron cargar los tipos de estado de cita');
                this.cargando = false;
            }
        });
    }

    puedeUnirseAVideollamada(): boolean {
        if (!this.citaSeleccionada || this.citaSeleccionada.tipo_cita !== 'telemática') return false;

        const fechaHora = new Date(`${this.citaSeleccionada.fecha}T${this.citaSeleccionada.hora}`);
        const ahora = new Date();

        const cincoMinAntes = new Date(fechaHora.getTime() - 5 * 60 * 1000);
        const treintaMinDespues = new Date(fechaHora.getTime() + 30 * 60 * 1000);

        return ahora >= cincoMinAntes && ahora <= treintaMinDespues;
    }

    unirseAVideollamada(): void {
        if (!this.citaSeleccionada) return;

        //Si es especialista, pedimos al padre que abra el modal de historial.
        if (this.esEspecialista) {
            const idPaciente = this.obtenerPacienteIdDeCita();
            const hoy = new Date();
            const yyyy = hoy.getFullYear();
            const mm = String(hoy.getMonth() + 1).padStart(2, '0');
            const dd = String(hoy.getDate()).padStart(2, '0');

            this.irACita.emit({
                id_paciente: idPaciente ?? null,
                id_cita: this.citaSeleccionada.id,
                fecha: `${yyyy}-${mm}-${dd}`,
                nombre_paciente: this.obtenerPropiedad('nombre_paciente') || '',
                dni_paciente: this.obtenerPropiedad('dni_paciente') || ''
            });
        }

        //Abrir la videollamada. Lo hacemos en el siguiente tick para que Angular procese el cierre del modal-info.
        setTimeout(() => {
            unirseConferencia(this.citaSeleccionada!.id, this.http, this.snackBar, environment.apiBase);
        }, 0);
    }

    irALaCita(): void {
        if (!this.citaSeleccionada) return;

        const idPaciente = this.obtenerPacienteIdDeCita(); //esto puede ser null
        const hoy = new Date();
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd = String(hoy.getDate()).padStart(2, '0');

        this.irACita.emit({
            id_paciente: idPaciente ?? null,
            id_cita: this.citaSeleccionada.id,
            fecha: `${yyyy}-${mm}-${dd}`,
            nombre_paciente: this.obtenerPropiedad('nombre_paciente') || '',
            dni_paciente: this.obtenerPropiedad('dni_paciente') || ''
        });
    }

    private recalcularBotones(): void {
        this.evaluarCancelacion();
        this.mostrarUnirse = this.calcularPuedeUnirse();          //telemática
        this.mostrarIrAPresencial = this.calcularPuedeIrPresencial(); //presencial
    }

    private normalizarFechaHora(fecha: string, hora: string | undefined): Date {
        //Aseguramos el formato HH:mm
        const hhmm = (hora ?? '00:00').slice(0, 5);
        return new Date(`${fecha}T${hhmm}`);
    }

    private calcularPuedeUnirse(): boolean {
        // Ya lo tienes: usa tu helper para telemática (5 min antes, 30 después)
        return this.citaSeleccionada ? mostrarBotonVideollamada(this.citaSeleccionada, { minutosAntes: 5, minutosDespues: 30 }) : false;
    }

    private obtenerPacienteIdDeCita(): number | null {
        if (!this.citaSeleccionada) return null;
        const c: any = this.citaSeleccionada;
        return typeof c.id_paciente === 'number' ? c.id_paciente
            : typeof c.paciente_id === 'number' ? c.paciente_id
                : c.paciente?.id ?? null;
    }

    private calcularPuedeIrPresencial(): boolean {
        if (!this.citaSeleccionada) return false;

        const esPresencial = (this.citaSeleccionada.tipo_cita || '').toLowerCase().includes('presencial');
        if (!esPresencial) return false;

        const fechaHora = construirFechaHoraLocal(this.citaSeleccionada.fecha, this.citaSeleccionada.hora);
        if (isNaN(fechaHora.getTime())) return false;

        const ahora = new Date();
        const cincoMinAntes = new Date(fechaHora.getTime() - 5 * 60 * 1000);
        const noventaMinDespues = new Date(fechaHora.getTime() + 90 * 60 * 1000);

        return ahora >= cincoMinAntes && ahora <= noventaMinDespues;
    }

    
}
