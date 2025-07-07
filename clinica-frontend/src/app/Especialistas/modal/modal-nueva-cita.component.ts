import { Component, EventEmitter, Output, Input, OnInit, HostListener, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { UserService } from '../../service/User-Service/user.service';
import { Paciente } from '../../models/paciente.model';

@Component({
    selector: 'app-modal-nueva-cita',
    standalone: true,
    templateUrl: './modal-nueva-cita.component.html',
    imports: [CommonModule, FormsModule, MatSnackBarModule]
})
export class ModalNuevaCitaComponent implements OnInit, OnChanges {
    @Input() modalVisible!: boolean;

    @Output() creada = new EventEmitter<void>();
    @Output() cerrado = new EventEmitter<void>();

    pacientes: Paciente[] = [];

    pacienteSeleccionado: number | null = null;
    fecha: string = '';
    hora: string = '';
    tipoCita: 'presencial' | 'telemática' = 'presencial';
    cargando = false;
    dateError: string | null = null;
    horasDisponibles: string[] = [];

    diasNoLaborables: string[] = [];
    horarioLaboral: any = null;
    minDate: string = '';

    constructor(private UserService: UserService, private snackBar: MatSnackBar) { }

    ngOnInit(): void {
        this.limiteCalendario();
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['modalVisible'].currentValue === true) {
            this.cargarPacientes();
            this.cargarConfiguracion();
            this.resetFormulario();
        }
    }

    private cargarPacientes(): void {
        this.UserService.listarPacientes().subscribe({
            next: (data) => {
                this.pacientes = data.pacientes || [];
                console.log('Pacientes en modal especialista:', this.pacientes);
            },
            error: () => {
                this.snackBar.open('Error al cargar pacientes', 'Cerrar', { duration: 3000 });
            }
        });
    }

    private cargarConfiguracion(): void {
        this.UserService.getConfiguracion().subscribe({
            next: (config: any) => {
                this.diasNoLaborables = config.dias_no_laborables || [];
                this.horarioLaboral = config.horario_laboral || null;
            },
            error: () => {
                this.snackBar.open('Error al cargar configuración general', 'Cerrar', { duration: 3000 });
            }
        });
    }

    private resetFormulario(): void {
        this.pacienteSeleccionado = null;
        this.fecha = '';
        this.hora = '';
        this.tipoCita = 'presencial';
        this.dateError = null;
        this.horasDisponibles = [];
    }

    onFechaChange(): void {
        this.hora = '';
        this.horasDisponibles = [];
        this.validarDia();
        if (this.fecha && !this.dateError) {
            this.cargarHorasDisponibles();
        }
    }

    private cargarHorasDisponibles(): void {
        if (!this.fecha) return;
        // Pasamos null para que el backend use el usuario autenticado como especialista
        this.UserService.getHorasDisponibles(null, this.fecha).subscribe({
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

    private limiteCalendario(): void {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = (today.getMonth() + 1).toString().padStart(2, '0');
        const dd = today.getDate().toString().padStart(2, '0');
        this.minDate = `${yyyy}-${mm}-${dd}`;
    }

    private esFinDeSemana(date: Date): boolean {
        const day = date.getDay();
        return day === 0 || day === 6;
    }

    private esFestivo(date: Date): boolean {
        const dateStr = date.toISOString().split('T')[0];
        return this.diasNoLaborables.includes(dateStr);
    }

    private esDiaValido(dateStr: string): boolean {
        const date = new Date(dateStr);
        if (this.esFinDeSemana(date)) return false;
        if (this.esFestivo(date)) return false;
        return true;
    }

    validarDia(): void {
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

    confirmar(): void {
        if (!this.pacienteSeleccionado || !this.fecha || !this.hora || !this.tipoCita) {
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

        // NOTA: No enviamos especialista_id porque backend lo infiere del usuario autenticado
        this.UserService.crearCita({
            paciente_id: this.pacienteSeleccionado,
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
                this.cargando = false;
            },
            complete: () => this.cargando = false
        });
    }

    cerrar(): void {
        this.cerrado.emit();
    }

    //Para utilizar el botón de escape para cerrar el modal
    @HostListener('document:keydown.escape', ['$event'])
    handleEscapeKey(event: KeyboardEvent) {
        if (this.modalVisible) this.cerrar();
    }
}
