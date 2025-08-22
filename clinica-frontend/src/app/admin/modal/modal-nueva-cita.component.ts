import { Component, EventEmitter, Output, Input, OnInit, HostListener, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgSelectModule } from '@ng-select/ng-select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { UserService } from '../../service/User-Service/user.service';
import { Paciente } from '../../models/paciente.model';
import { Especialista } from '../../models/especialista.model';

@Component({
    selector: 'app-modal-nueva-cita',
    standalone: true,
    templateUrl: './modal-nueva-cita.component.html',
    imports: [CommonModule, FormsModule, MatSnackBarModule, NgSelectModule]
})
export class ModalNuevaCitaComponent implements OnInit, OnChanges {
    @Input() modalVisible!: boolean;
    @Input() idEspecialista!: number;
    especialidades: string[] = [];
    @Input() todosLosEspecialistas: Especialista[] = [];

    @Output() creada = new EventEmitter<void>();
    @Output() cerrado = new EventEmitter<void>();

    pacientes: Paciente[] = [];
    especialistas: Especialista[] = [];
    pacientesOrdenados: any[] = [];
    especialistasFiltrados: Especialista[] = [];
    especialistasFiltradosOrdenados: any[] = [];

    especialistaSeleccionado: number | null = null;
    especialidadSeleccionada: string | null = null;
    pacienteSeleccionado: number | null = null;
    fecha: string = '';
    hora: string = '';
    tipoCita: 'presencial' | 'telemática' = 'presencial';
    comentarios: string = '';
    cargando = false;
    dateError: string | null = null;
    horasDisponibles: string[] = [];

    diasNoLaborables: string[] = [];
    horarioLaboral: any = null;
    minDate: string = '';

    constructor(private UserService: UserService, private snackBar: MatSnackBar) { }

    ngOnInit(): void {
        this.especialistasFiltrados = [...this.especialistas];
        this.setMinDate();
        this.obtenerPacientes();
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['modalVisible'] && changes['modalVisible'].currentValue === true) {
            this.inicializarModal();
        }
    }

    private async inicializarModal(): Promise<void> {
        this.cargando = true;
        this.dateError = null;
        this.resetFormulario();

        try {
            await Promise.all([
                this.obtenerPacientes(),
                this.cargarDatos(),
                this.cargarConfiguracion()
            ]);
        } catch (error) {
            this.snackBar.open('Error al cargar datos iniciales', 'Cerrar', { duration: 3000 });
        } finally {
            this.cargando = false;
        }
    }

    private resetFormulario(): void {
        this.pacienteSeleccionado = null;
        this.especialidadSeleccionada = null;
        this.especialistaSeleccionado = null;
        this.fecha = '';
        this.hora = '';
        this.tipoCita = 'presencial';
        this.comentarios = '';
        this.dateError = null;
        this.horasDisponibles = [];
        this.especialistasFiltrados = [];
        this.especialistasFiltradosOrdenados = [];
    }

    obtenerPacientes(): Promise<void> {
        return new Promise((resolve, reject) => {
            this.UserService.listarPacientes().subscribe({
                next: (data) => {
                    this.pacientes = data.pacientes || [];
                    this.pacientesOrdenados = this.pacientes
                        .map(p => ({
                            ...p,
                            nombreCompleto: `${p.user?.nombre ?? ''} ${p.user?.apellidos ?? ''}`.trim()
                        }))
                        .sort((a, b) => a.nombreCompleto.localeCompare(b.nombreCompleto));
                    resolve();
                },
                error: () => {
                    this.snackBar.open('Error al cargar pacientes', 'Cerrar', { duration: 3000 });
                    reject();
                }
            });
        });
    }

    private cargarDatos(): Promise<void> {
        return new Promise((resolve, reject) => {
            this.UserService.getEspecialidades().subscribe({
                next: (data) => {
                    this.especialidades = data;
                    resolve();
                },
                error: () => {
                    this.snackBar.open('Error al cargar especialidades', 'Cerrar', { duration: 3000 });
                    reject();
                }
            });
        });
    }

    private cargarConfiguracion(): Promise<void> {
        return new Promise((resolve, reject) => {
            this.UserService.getConfiguracion().subscribe({
                next: (config: any) => {
                    this.diasNoLaborables = config.dias_no_laborables || [];
                    this.horarioLaboral = config.horario_laboral || null;
                    resolve();
                },
                error: () => {
                    this.snackBar.open('Error al cargar configuración general', 'Cerrar', { duration: 3000 });
                    reject();
                }
            });
        });
    }

    filtrarEspecialistasPorEspecialidad(): void {
        if (!this.especialidadSeleccionada) {
            this.especialistasFiltrados = [];
            this.especialistasFiltradosOrdenados = [];
        } else {
            this.UserService.getEspecialistasPorEspecialidad(this.especialidadSeleccionada).subscribe({
                next: (data) => {
                    this.especialistasFiltrados = data;
                    this.especialistasFiltradosOrdenados = this.especialistasFiltrados
                        .map(e => ({
                            ...e,
                            nombreCompleto: `${e.user?.nombre ?? ''} ${e.user?.apellidos ?? ''}`.trim()
                        }))
                        .sort((a, b) => a.nombreCompleto.localeCompare(b.nombreCompleto));
                },
                error: () => {
                    this.snackBar.open('Error al cargar especialistas por especialidad', 'Cerrar', { duration: 3000 });
                    this.especialistasFiltrados = [];
                    this.especialistasFiltradosOrdenados = [];
                }
            });
        }
        this.especialistaSeleccionado = null;
    }

    onEspecialistaChange(): void {
        this.hora = '';
        this.horasDisponibles = [];
        if (this.especialistaSeleccionado && this.fecha) {
            this.cargarHorasDisponibles();
        }
    }

    onFechaChange(): void {
        this.hora = '';
        this.horasDisponibles = [];
        this.validateDate();
        if (this.especialistaSeleccionado && this.fecha && !this.dateError) {
            this.cargarHorasDisponibles();
        }
    }

    private cargarHorasDisponibles(): void {
        this.UserService.getHorasDisponibles(this.especialistaSeleccionado!, this.fecha).subscribe({
            next: (response: any) => {
                this.horasDisponibles = response.horas_disponibles || [];
                if (this.horasDisponibles.length === 0) {
                    this.snackBar.open('No hay horas disponibles para la fecha seleccionada', 'Cerrar', { duration: 4000 });
                }
            },
            error: () => {
                this.snackBar.open('Error al cargar horas disponibles', 'Cerrar', { duration: 3000 });
            }
        });
    }

    confirmar(): void {
        if (!this.pacienteSeleccionado || !this.especialistaSeleccionado || !this.fecha || !this.hora || !this.tipoCita) {
            this.snackBar.open('Complete todos los campos obligatorios', 'Cerrar', { duration: 3000 });
            return;
        }

        if (!this.isDateValid(this.fecha)) {
            this.dateError = 'La fecha seleccionada no es válida. No se permiten fines de semana ni días festivos.';
            return;
        } else {
            this.dateError = null;
        }

        this.cargando = true;

        //probando la verificación de la hora local, en lugar de UTC
        const [year, month, day] = this.fecha.split('-');
        const [hour, minute] = this.hora.split(':');
        const fechaHora = `${year}-${month}-${day} ${hour}:${minute}:00`;

        this.UserService.crearCita({
            especialista_id: this.especialistaSeleccionado,
            paciente_id: this.pacienteSeleccionado,
            fecha_hora_cita: fechaHora,
            tipo_cita: this.tipoCita,
            comentario: this.comentarios || null


        }).subscribe({
            next: () => {
                this.snackBar.open('Cita creada correctamente', 'Cerrar', { duration: 3000 });
                this.creada.emit();
                console.log('Creando cita con:', {
                    especialista_id: this.especialistaSeleccionado,
                    paciente_id: this.pacienteSeleccionado,
                    fecha_hora_cita: fechaHora,
                    tipo_cita: this.tipoCita,
                });
            },
            error: (error) => {
                console.log('Creando cita con:', {
                    especialista_id: this.especialistaSeleccionado,
                    paciente_id: this.pacienteSeleccionado,
                    fecha_hora_cita: fechaHora,
                    tipo_cita: this.tipoCita,
                });
                let mensaje = 'Error al crear la cita';
                const errores = error.error?.errors;

                //Para mostrar el mensaje completo de error, en caso de que ocurra
                if (errores && typeof errores === 'object') {
                    const primeraClave = Object.keys(errores)[0];
                    if (errores[primeraClave]?.length) {
                        mensaje = errores[primeraClave][0];
                    }
                } else if (error.error?.message) {
                    mensaje = error.error.message;
                }

                this.snackBar.open(mensaje, 'Cerrar', { duration: 4000 });
                console.error('Errores de validación:', error.error?.errors);
                this.cargando = false;
            },
            complete: () => {
                this.cargando = false;
                this.cerrar();
            }
        });
    }

    cerrar(): void {
        this.resetFormulario();
        this.cerrado.emit();
    }

    validateDate(): void {
        if (!this.fecha) {
            this.dateError = 'La fecha es obligatoria.';
            return;
        }
        if (!this.isDateValid(this.fecha)) {
            this.dateError = 'La fecha seleccionada no es válida. No se permiten fines de semana ni días festivos.';
        } else {
            this.dateError = null;
        }
    }

    private setMinDate(): void {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = (today.getMonth() + 1).toString().padStart(2, '0');
        const dd = today.getDate().toString().padStart(2, '0');
        this.minDate = `${yyyy}-${mm}-${dd}`;
    }

    private isWeekend(date: Date): boolean {
        const day = date.getDay();
        return day === 0 || day === 6;
    }

    private isHoliday(date: Date): boolean {
        const dateStr = date.toISOString().split('T')[0];
        return this.diasNoLaborables.includes(dateStr);
    }

    private isDateValid(dateStr: string): boolean {
        const date = new Date(dateStr);
        if (this.isWeekend(date) || this.isHoliday(date)) {
            return false;
        }
        return true;
    }

    @HostListener('document:keydown.escape', ['$event'])
    handleEscapeKey(event: KeyboardEvent) {
        if (this.modalVisible) this.cerrar();
    }
}
