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
    especialistasFiltrados: Especialista[] = [];
    fecha: string = '';
    hora: string = '';
    tipoCita: 'presencial' | 'teleática' = 'presencial';
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
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['modalVisible'] && changes['modalVisible'].currentValue === true) {
            this.cargarDatos();
            this.cargarConfiguracion();
            this.dateError = null;
            this.fecha = '';
            this.hora = '';
            this.horasDisponibles = [];
        }
    }

    private cargarDatos(): void {

        this.UserService.getEspecialidades().subscribe({
            next: (data) => this.especialidades = data,
            error: () => this.snackBar.open('Error al cargar especialidades', 'Cerrar', { duration: 3000 })
        });
    }

    private cargarConfiguracion(): void {
        this.UserService.getConfiguracion().subscribe({
            next: (config: any) => {
                this.diasNoLaborables = config.dias_no_laborables || [];
                this.horarioLaboral = config.horario_laboral || null;
            },
            error: () => this.snackBar.open('Error al cargar configuración general', 'Cerrar', { duration: 3000 })
        });
    }

    onCambioEspecialista(): void {
        this.hora = '';
        this.horasDisponibles = [];
        if (this.especialistaSeleccionado && this.fecha) {
            this.cargarHorasDisponibles();
        }
    }

    onCambioFecha(): void {
        this.hora = '';
        this.horasDisponibles = [];
        this.diaValido();
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

    private esFinDeSemana(date: Date): boolean {
        const day = date.getDay();
        return day === 0 || day === 6; // Sunday = 0, Saturday = 6
    }

    private esFestivo(date: Date): boolean {
        const dateStr = date.toISOString().split('T')[0];
        return this.diasNoLaborables.includes(dateStr);
    }

    private esDiaValido(dateStr: string): boolean {
        const date = new Date(dateStr);
        if (this.esFinDeSemana(date)) {
            return false;
        }
        if (this.esFestivo(date)) {
            return false;
        }
        return true;
    }

    confirmar(): void {
        if (!this.especialistaSeleccionado || !this.fecha || !this.hora || !this.tipoCita) {
            this.snackBar.open('Complete todos los campos obligatorios', 'Cerrar', { duration: 3000 });
            return;
        }

        if (!this.esDiaValido(this.fecha)) {
            this.dateError = 'La fecha seleccionada no es válida. No se permiten fines de semana ni días festivos.';
            return;
        } else {
            this.dateError = null;
        }

        this.cargando = true;

        const fechaHora = `${this.fecha} ${this.hora}:00`;

        this.UserService.crearCita({
            especialista_id: this.especialistaSeleccionado,
            fecha_hora_cita: fechaHora,
            tipo_cita: this.tipoCita,
        }).subscribe({
            next: () => {
                this.snackBar.open('Cita creada correctamente', 'Cerrar', { duration: 3000 });
                this.creada.emit();
                this.cerrar();
            },
            error: () => {
                this.snackBar.open('Error al crear la cita', 'Cerrar', { duration: 3000 });
            },
            complete: () => this.cargando = false
        });
    }

    cerrar(): void {
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
            console.log('Listando especialistas for especialidad:', this.especialidadSeleccionada);
            this.UserService.getEspecialistasPorEspecialidad(this.especialidadSeleccionada).subscribe({
                next: (datos) => {
                    console.log('Especialistas recibidos:', datos);
                    this.especialistasFiltrados = datos;
                },
                error: (error) => {
                    console.error('Error especialistas:', error);
                    this.especialistasFiltrados = [];
                    this.snackBar.open('Error al cargar especialistas por especialidad', 'Cerrar', { duration: 3000 });
                }
            });
        }
        // Reiniciar el especialista seleccionado
        this.especialistaSeleccionado = null;
    }

    diaValido(): void {
        if (!this.fecha) {
            this.dateError = 'La fecha es obligatoria.';
            return;
        }
        if (!this.esDiaValido(this.fecha)) {
            this.dateError = 'La fecha seleccionada no es válida. No se permiten fines de semana ni días festivos.';
        } else {
            this.dateError = null;
        }
    }


}
