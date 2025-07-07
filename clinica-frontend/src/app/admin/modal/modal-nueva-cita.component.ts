import { Component, EventEmitter, Output, Input, OnInit, HostListener, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { UserService } from '../../service/User-Service/user.service';
import { Paciente } from '../../models/paciente.model';
import { Especialista } from '../../models/especialista.model';

@Component({
    selector: 'app-modal-nueva-cita',
    standalone: true,
    templateUrl: './modal-nueva-cita.component.html',
    imports: [CommonModule, FormsModule, MatSnackBarModule]
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

    especialistaSeleccionado: number | null = null;
    especialidadSeleccionada: string | null = null;
    pacienteSeleccionado: number | null = null;
    especialistasFiltrados: Especialista[] = [];
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
    }

    obtenerPacientes(): Promise<void> {
        return new Promise((resolve, reject) => {
            this.UserService.listarPacientes().subscribe({
                next: (data) => {
                    this.pacientes = data.pacientes || [];
                    console.log('Pacientes recibidos en modal:', this.pacientes);
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
                    console.log('Especialidades recibidas:', this.especialidades);
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

    private setMinDate(): void {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = (today.getMonth() + 1).toString().padStart(2, '0');
        const dd = today.getDate().toString().padStart(2, '0');
        this.minDate = `${yyyy}-${mm}-${dd}`;
    }

    private isWeekend(date: Date): boolean {
        const day = date.getDay();
        return day === 0 || day === 6; // Sunday = 0, Saturday = 6
    }

    private isHoliday(date: Date): boolean {
        const dateStr = date.toISOString().split('T')[0];
        return this.diasNoLaborables.includes(dateStr);
    }

    private isDateValid(dateStr: string): boolean {
        const date = new Date(dateStr);
        if (this.isWeekend(date)) {
            return false;
        }
        if (this.isHoliday(date)) {
            return false;
        }
        return true;
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

        const fechaHora = `${this.fecha} ${this.hora}:00`;

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
            },
            error: () => {
                this.snackBar.open('Error al crear la cita', 'Cerrar', { duration: 3000 });
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

    @HostListener('document:keydown.escape', ['$event'])
    handleEscapeKey(event: KeyboardEvent) {
        if (this.modalVisible) this.cerrar();
    }

    filtrarEspecialistasPorEspecialidad(): void {
        if (!this.especialidadSeleccionada) {
            this.especialistasFiltrados = [];
        } else {
            console.log('Fetching especialistas for especialidad:', this.especialidadSeleccionada);
            this.UserService.getEspecialistasPorEspecialidad(this.especialidadSeleccionada).subscribe({
                next: (data) => {
                    console.log('Received especialistas:', data);
                    this.especialistasFiltrados = data;
                },
                error: (err) => {
                    console.error('Error fetching especialistas:', err);
                    this.especialistasFiltrados = [];
                    this.snackBar.open('Error al cargar especialistas por especialidad', 'Cerrar', { duration: 3000 });
                }
            });
        }
        this.especialistaSeleccionado = null;
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
}
