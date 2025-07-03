import { Component, EventEmitter, Input, Output, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { CitaListado } from '../../models/listarCitas.model';
import { Paciente } from '../../models/paciente.model';
import { Especialista } from '../../models/especialista.model';
import { UserService } from '../../service/User-Service/user.service';

@Component({
    selector: 'app-modal-edit-cita',
    standalone: true,
    imports: [CommonModule, FormsModule, MatSnackBarModule],
    templateUrl: './modal-edit-cita.component.html',
})
export class ModalEditCitaComponent implements OnChanges {
    @Input() visible: boolean = false;
    @Input() cita!: CitaListado;
    @Input() pacientes: Paciente[] = [];
    @Input() especialistas: Especialista[] = [];
    @Input() configuracion: any;

    @Output() cerrar = new EventEmitter<void>();
    @Output() guardar = new EventEmitter<CitaListado>();

    citaForm!: CitaListado;
    errores: { [campo: string]: string } = {};
    horasDisponibles: string[] = [];
    especialidadSeleccionada: string = '';
    especialidades: string[] = [];
    tiposEstado: string[] = [];

    formularioCargado: boolean = false;
    citaPasada: boolean = true;

    constructor(private snackBar: MatSnackBar, private userService: UserService) { }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['cita'] && this.cita) {
            this.formularioCargado = false;
            this.citaForm = {
                ...this.cita,
                id_paciente: Number(this.cita.id_paciente),
                id_especialista: Number(this.cita.id_especialista)
            };

            this.especialidadSeleccionada = this.cita.especialidad || '';

            this.userService.getEspecialidades().subscribe({
                next: (res) => {
                    this.especialidades = res.sort();

                    this.filtrarEspecialistas(() => {
                        if (this.citaForm.id_especialista && this.citaForm.fecha) {
                            this.calcularHorasDisponibles(() => {
                                this.formularioCargado = true;
                            });
                        } else {
                            this.formularioCargado = true;
                        }
                    });
                },
                error: () => {
                    this.snackBar.open('Error al cargar especialidades', 'Cerrar', { duration: 3000 });
                    this.formularioCargado = true;
                }
            });

            this.userService.getTiposEstadoCita().subscribe({
                next: (res) => {
                    if (res.success) {
                        this.tiposEstado = res.tipos_estado;
                    }
                },
                error: () => {
                    this.snackBar.open('Error al cargar los estados de cita', 'Cerrar', { duration: 3000 });
                }
            });

            //Cita pasada
            const fechaHoraCita = new Date(`${this.citaForm.fecha}T${this.citaForm.hora}`);
            const ahora = new Date();
            this.citaPasada = fechaHoraCita < ahora;
        }
    }

    cerrarModal(): void {
        console.log('Datos a guardar:', this.citaForm, {
            id_especialista: typeof this.citaForm.id_especialista,
            id_paciente: typeof this.citaForm.id_paciente
        });
        this.cerrar.emit();
        this.errores = {};
    }

    onSubmit(): void {
        if (!this.citaForm.fecha || !this.citaForm.hora || !this.citaForm.id_paciente || !this.citaForm.id_especialista) {
            this.snackBar.open('Todos los campos son obligatorios', 'Cerrar', { duration: 3000 });
            return;
        }

        this.guardar.emit({ ...this.citaForm });
    }

    cargarEspecialidades(): void {
        this.userService.getEspecialidades().subscribe({
            next: (res) => this.especialidades = res.sort(),
            error: () => this.snackBar.open('Error al cargar especialidades', 'Cerrar', { duration: 3000 })
        });
    }

    filtrarEspecialistas(callback?: () => void): void {
        if (!this.especialidadSeleccionada) {
            this.especialistas = [];
            if (callback) callback();
            return;
        }

        this.userService.getEspecialistasPorEspecialidad(this.especialidadSeleccionada).subscribe({
            next: (res) => {
                this.especialistas = res.sort((a, b) => {
                    const nombreA = a.usuario?.nombre ?? '';
                    const apellidosA = a.usuario?.apellidos ?? '';
                    const nombreB = b.usuario?.nombre ?? '';
                    const apellidosB = b.usuario?.apellidos ?? '';
                    return (nombreA + apellidosA).localeCompare(nombreB + apellidosB);
                });

                if (callback) callback();
            },
            error: () => {
                this.snackBar.open('Error al filtrar especialistas', 'Cerrar', { duration: 3000 });
                this.especialistas = [];
                if (callback) callback(); // Llamar incluso si hay error para que no se bloquee la lógica
            }
        });
    }

    alCambiarEspecialidad(): void {
        this.citaForm.id_especialista = 0;
        this.horasDisponibles = [];
        this.citaForm.hora = '';

        this.filtrarEspecialistas(() => this.calcularHorasDisponibles());
    }

    alCambiarFecha(): void {
        this.citaForm.hora = '';
        this.obtenerHorasDesdeBackend();
    }

    alCambiarEspecialista(): void {
        this.citaForm.hora = '';
        this.obtenerHorasDesdeBackend();
    }

    obtenerHorasDesdeBackend(): void {
        const fecha = this.citaForm.fecha;
        const id = this.citaForm.id_especialista;

        if (!fecha || !id) {
            this.horasDisponibles = [];
            return;
        }

        if (this.configuracion.festivos.includes(fecha)) {
            this.snackBar.open('No se pueden asignar citas en días festivos', 'Cerrar', { duration: 3000 });
            this.horasDisponibles = [];
            return;
        }

        const hoy = new Date();
        const seleccionada = new Date(fecha);
        if (seleccionada.setHours(0, 0, 0, 0) < hoy.setHours(0, 0, 0, 0)) {
            this.snackBar.open('No puedes seleccionar fechas pasadas', 'Cerrar', { duration: 3000 });
            this.horasDisponibles = [];
            return;
        }

        this.userService.getHorasDisponibles(id, fecha).subscribe({
            next: (horas) => {
                this.horasDisponibles = horas.horas_disponibles?.map(h => h.trim().substring(0, 5)) || [];
                console.log('Horas recibidas desde backend:', horas)
            },
            error: () => {
                this.snackBar.open('Error al obtener horas disponibles', 'Cerrar', { duration: 3000 });
                this.horasDisponibles = [];
            }
        });
    }


    calcularHorasDisponibles(callback?: () => void): void {
        const { id_especialista, fecha, hora } = this.citaForm;

        if (!id_especialista || !fecha || this.configuracion.festivos.includes(fecha)) {
            this.horasDisponibles = [];
            if (callback) callback();
            return;
        }

        this.userService.getHorasDisponibles(id_especialista, fecha).subscribe({
            next: (res) => {
                const horasNormalizadas = (res.horas_disponibles ?? []).map(h => h.trim().substring(0, 5));
                const horaActual = hora?.trim().substring(0, 5);

                if (horaActual && !horasNormalizadas.includes(horaActual)) {
                    horasNormalizadas.unshift(horaActual);
                }

                this.horasDisponibles = horasNormalizadas;

                console.log('Horas disponibles normalizadas:', this.horasDisponibles);
                console.log('Hora actual del formulario:', horaActual);

                if (callback) callback();
            },
            error: () => {
                this.snackBar.open('No se pudieron cargar las horas disponibles', 'Cerrar', { duration: 3000 });
                this.horasDisponibles = [];
                if (callback) callback();
            }
        });
    }
}
