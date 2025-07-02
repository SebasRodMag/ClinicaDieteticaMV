import { Component, OnInit, TemplateRef, ViewChild, AfterViewInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { UserService } from '../service/User-Service/user.service';
import { CitaListado } from '../models/listarCitas.model';
import { Paciente } from '../models/paciente.model';
import { Especialista } from '../models/especialista.model';
import { CitaActualizar } from '../models/citaActualizar.model';
import { ModalEditCitaComponent } from './modal/modal-edit-cita.component';

@Component({
    selector: 'app-admin-citas',
    standalone: true,
    imports: [CommonModule, FormsModule, MatSnackBarModule, TablaDatosComponent, ModalEditCitaComponent],
    templateUrl: './citas-list.component.html',
})
export class CitasListComponent implements OnInit, AfterViewInit {
    citas: CitaListado[] = [];
    citasFiltradas: CitaListado[] = [];
    citaSeleccionada!: CitaListado;
    pacientes: Paciente[] = [];
    especialistas: Especialista[] = [];
    especialidades: string[] = [];
    especialidadSeleccionada: string = '';
    filtroEspecialista: string = '';

    filtro: string = '';
    filtroFecha: string = '';
    loading: boolean = false;
    huboError: boolean = false;
    modalVisible = false;

    columnas: string[] = ['id_cita', 'fecha', 'hora', 'nombre_paciente', 'nombre_especialista', 'especialidad', 'tipo_cita', 'estado', 'accion'];
    templatesMap: { [key: string]: TemplateRef<any> } = {};

    columnaOrden: string | null = null;
    direccionOrdenAsc: boolean = true;
    paginaActual = 1;
    itemsPorPagina = 10;
    maxPaginasVisibles = 5;

    configuracion: {
        festivos: string[],
        horario: { apertura: string, cierre: string },
        duracion: number,
        especialidades: string[]
    } = {
            festivos: [],
            horario: { apertura: '08:00', cierre: '16:00' },
            duracion: 30,
            especialidades: []
        };

    @ViewChild('fecha', { static: true }) fechaTemplate!: TemplateRef<any>;
    @ViewChild('accion', { static: true }) accionTemplate!: TemplateRef<any>;

    constructor(private userService: UserService, private snackBar: MatSnackBar, private cdr: ChangeDetectorRef) { }

    ngOnInit(): void {
        this.filtroFecha = new Date().toISOString().split('T')[0];

        this.obtenerCitas();
        this.obtenerPacientes();
        this.obtenerConfiguracion();
    }

    ngAfterViewInit(): void {
        this.templatesMap = {
            fecha: this.fechaTemplate,
            accion: this.accionTemplate,
        };
        this.cdr.detectChanges();
    }

    obtenerPacientes(): void {
        this.userService.listarPacientes().subscribe({
            next: (res) => {
                this.pacientes = res.pacientes;
                console.log('Pacientes recibidos:', this.pacientes);
            },
            error: () => this.snackBar.open('Error al cargar pacientes', 'Cerrar', { duration: 3000 }),
        });
    }

    obtenerEspecialistasPorEspecialidad(especialidad: string): void {
        this.userService.getEspecialistasPorEspecialidad(especialidad).subscribe({
            next: (res) => {
                // Orden alfabético
                this.especialistas = res.sort((a, b) =>
                    (a.usuario.nombre + a.usuario.apellidos).localeCompare(b.usuario.nombre + b.usuario.apellidos)
                );
            },
            error: () => {
                this.especialistas = [];
                this.snackBar.open('Error al cargar especialistas por especialidad', 'Cerrar', { duration: 3000 });
            }
        });
    }

    obtenerConfiguracion(): void {
        this.userService.getConfiguracion().subscribe({
            next: (res) => {
                const config = res.configuraciones;
                this.configuracion = {
                    festivos: config['dias_no_laborables'] || [],
                    horario: config['horario_laboral'] || { apertura: '09:00', cierre: '17:00' },
                    duracion: config['duracion_cita'] || 30,
                    especialidades: config['Especialidades'] || []
                };
                this.especialidades = this.configuracion.especialidades;
            },
            error: () => {
                this.snackBar.open('Error al cargar configuración de la clínica', 'Cerrar', { duration: 3000 });
            }
        });
    }

    /*     onEspecialidadSeleccionada(): void {
            if (this.especialidadSeleccionada) {
                this.obtenerEspecialistasPorEspecialidad(this.especialidadSeleccionada);
            } else {
                this.especialistas = [];
            }
            this.filtroEspecialista = '';
            this.filtrarCitas();
        } */

    obtenerCitas(): void {
        this.loading = true;
        this.huboError = false;

        this.userService.obtenerTodasLasCitas().subscribe({
            next: (respuesta) => {
                this.citas = respuesta.citas || [];
                console.log('Citas desde el backend: ', this.citas);
                this.filtrarCitas();
                this.loading = false;
            },
            error: () => {
                this.huboError = true;
                this.loading = false;
                this.snackBar.open('Error al cargar citas', 'Cerrar', { duration: 3000 });
            },
        });
    }

    filtrarCitas(): void {
        const texto = this.filtro.toLowerCase().trim();
        const filtroEsp = this.filtroEspecialista;

        this.citasFiltradas = this.citas.filter((cita) => {
            const coincideTexto =
                cita.nombre_paciente.toLowerCase().includes(texto) ||
                cita.nombre_especialista.toLowerCase().includes(texto) ||
                cita.tipo_cita.toLowerCase().includes(texto) ||
                cita.estado.toLowerCase().includes(texto);

            const coincideFecha = !this.filtroFecha || cita.fecha === this.filtroFecha;

            const coincideEspecialista = !filtroEsp || cita.id_especialista.toString() === filtroEsp;

            const coincideEspecialidad = !this.especialidadSeleccionada || cita.especialidad === this.especialidadSeleccionada;

            return coincideTexto && coincideFecha && coincideEspecialista && coincideEspecialidad;
        });

        this.ordenarDatos();
        this.paginaActual = 1;
    }

    aplicarFiltros(): void {
        this.filtrarCitas();
    }

    cambiarDia(dias: number): void {
        if (this.filtroFecha) {
            const fecha = new Date(this.filtroFecha);
            fecha.setDate(fecha.getDate() + dias);
            this.filtroFecha = fecha.toISOString().split('T')[0];
        } else {
            this.filtroFecha = new Date().toISOString().split('T')[0];
        }

        this.aplicarFiltros();
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

    ordenarDatos(): void {
        if (!this.columnaOrden) return;

        this.citasFiltradas.sort((a, b) => {
            const valorA = this.obtenerValorColumna(a, this.columnaOrden!);
            const valorB = this.obtenerValorColumna(b, this.columnaOrden!);
            return this.direccionOrdenAsc
                ? valorA.localeCompare(valorB)
                : valorB.localeCompare(valorA);
        });
    }

    obtenerValorColumna(cita: CitaListado, columna: string): string {
        switch (columna) {
            case 'id_cita': return cita.id_cita.toString();
            case 'fecha': return cita.fecha;
            case 'hora': return cita.hora;
            case 'nombre_paciente': return cita.nombre_paciente;
            case 'nombre_especialista': return cita.nombre_especialista;
            case 'tipo_cita': return cita.tipo_cita;
            case 'estado': return cita.estado;
            case 'especialidad': return cita.especialidad || '';
            default: return '';
        }
    }

    cambiarPagina(pagina: number): void {
        this.paginaActual = pagina;
    }

    editarCita(cita: CitaListado): void {
        this.citaSeleccionada = { ...cita };
        this.modalVisible = true;
    }

    cerrarModal(): void {
        this.modalVisible = false;
    }

    guardarCita(citaActualizada: CitaListado): void {
        if (
            !citaActualizada.id_cita ||
            !citaActualizada.id_paciente ||
            !citaActualizada.id_especialista ||
            !citaActualizada.fecha ||
            !citaActualizada.hora ||
            !citaActualizada.tipo_cita ||
            !citaActualizada.estado ||
            !citaActualizada.comentario
        ) {
            this.snackBar.open('Faltan datos obligatorios para actualizar la cita', 'Cerrar', { duration: 4000 });
            return;
        }

        const citaParaActualizar: CitaActualizar = {
            id_cita: citaActualizada.id_cita,
            id_paciente: citaActualizada.id_paciente,
            id_especialista: citaActualizada.id_especialista,
            fecha_hora_cita: `${citaActualizada.fecha} ${citaActualizada.hora}:00`,
            tipo_cita: citaActualizada.tipo_cita,
            estado: citaActualizada.estado,
            comentario: citaActualizada.comentario ?? null,
        };

        this.userService.actualizarCita(citaParaActualizar).subscribe({
            next: () => {
                this.snackBar.open('Cita actualizada correctamente', 'Cerrar', { duration: 3000 });
                this.modalVisible = false;
                this.obtenerCitas();
            },
            error: (error) => {
                const mensaje = error?.error?.message || 'Error al actualizar cita';
                this.snackBar.open(mensaje, 'Cerrar', { duration: 4000 });
            }
        });
    }
}
