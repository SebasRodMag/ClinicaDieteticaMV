import { Component, OnInit, ViewChild, TemplateRef, AfterViewInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { finalize } from 'rxjs';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';

import { Paciente } from '../models/paciente.model';
import { UserService } from '../service/User-Service/user.service';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { formatearFecha } from '../components/utilidades/sanitizar.utils';
import { ModalNuevoPacienteComponent } from './modal/modal-nuevo-paciente.component';
import { Usuario } from '../models/usuario.model';

type PacienteExtendido = Paciente & {
    nombre_paciente: string;
    nombre_especialista: string;
};

@Component({
    selector: 'app-pacientes-list',
    standalone: true,
    imports: [MatSnackBarModule, CommonModule, FormsModule, TablaDatosComponent, ModalNuevoPacienteComponent],
    templateUrl: './pacientes-list.component.html',
})
export class PacientesListComponent implements OnInit, AfterViewInit {
    idsUsuariosExcluidos: number[] = [];//Para excluir de la lista de usuarios disponibles en el modal
    pacientes: PacienteExtendido[] = [];
    loading = true;
    huboError = false;
    error = '';

    // Modal “Nuevo paciente”
    modalNuevoPacienteVisible = false;
    // Variables para paginación, ordenación y filtro
    filtro = '';
    columnaOrden: string | null = null;
    direccionOrdenAsc = true;
    itemsPorPagina = 10;
    paginaActual = 1;
    maxPaginasVisibles = 5;
    formatearFecha = formatearFecha;

    columnas = [
        'id',
        'nombre_paciente',
        'numero_historial',
        'fecha_alta',
        'estado_cita',
        'comentario',
        'nombre_especialista',
        'especialidad',
        'acciones'
    ];

    templatesMap: { [key: string]: TemplateRef<any> } = {};

    @ViewChild('accionesTemplate') accionesTemplate!: TemplateRef<any>;
    @ViewChild('nombrePacienteTemplate') nombrePacienteTemplate!: TemplateRef<any>;
    @ViewChild('numeroHistorialTemplate') numeroHistorialTemplate!: TemplateRef<any>;
    @ViewChild('estadoCitaTemplate') estadoCitaTemplate!: TemplateRef<any>;
    @ViewChild('comentarioTemplate') comentarioTemplate!: TemplateRef<any>;
    @ViewChild('nombreEspecialistaTemplate') nombreEspecialistaTemplate!: TemplateRef<any>;
    @ViewChild('especialidadTemplate') especialidadTemplate!: TemplateRef<any>;
    @ViewChild('fechaAltaTemplate') fechaAltaTemplate!: TemplateRef<any>;

    constructor(
        private userService: UserService,
        private snackBar: MatSnackBar,
        private cdr: ChangeDetectorRef,
        private router: Router
    ) { }

    ngOnInit(): void {
        this.cargarPacientes();
    }

    ngAfterViewInit(): void {
        // Mapeo de plantillas a las columnas
        this.templatesMap = {
            nombre_paciente: this.nombrePacienteTemplate,
            numero_historial: this.numeroHistorialTemplate,
            estado_cita: this.estadoCitaTemplate,
            comentario: this.comentarioTemplate,
            nombre_especialista: this.nombreEspecialistaTemplate,
            especialidad: this.especialidadTemplate,
            acciones: this.accionesTemplate,
            fecha_alta: this.fechaAltaTemplate,
        };

        // Asegura que Angular aplique los templates después de crearlos
        this.cdr.detectChanges();
    }

    mostrarMensaje(mensaje: string, tipo: 'success' | 'error') {
        this.snackBar.open(mensaje, 'Cerrar', {
            duration: 3000,
            panelClass: tipo === 'success' ? ['snackbar-success'] : ['snackbar-error'],
        });
    }

    cargarPacientes(): void {
        this.loading = true;
        this.error = '';
        this.huboError = false;

        // 👇 Usa any[] aquí porque la API puede traer `usuario` en lugar de `user`
        this.userService.pacientesConEspecialista()
            .pipe(finalize(() => (this.loading = false)))
            .subscribe({
                next: (data: any[]) => {
                    this.pacientes = data.map((raw): PacienteExtendido => {
                        // Normaliza el usuario del paciente: acepta `user` o `usuario`
                        const pacienteUser = (raw.user ?? raw.usuario) as Usuario | null;

                        // Normaliza el usuario del especialista: acepta `user` o `usuario` anidado
                        const especialistaUser = raw.ultima_cita?.especialista
                            ? (raw.ultima_cita.especialista.user ?? raw.ultima_cita.especialista.usuario) as Usuario | null
                            : null;

                        // Reconstruye el objeto Paciente conforme a tu interfaz
                        const pacienteBase: Paciente = {
                            ...raw,
                            user: pacienteUser,
                            ultima_cita: raw.ultima_cita
                                ? {
                                    ...raw.ultima_cita,
                                    especialista: raw.ultima_cita.especialista
                                        ? {
                                            ...raw.ultima_cita.especialista,
                                            user: especialistaUser,
                                        }
                                        : null,
                                }
                                : null,
                        };

                        // Propiedades planas para filtros y ordenación
                        const nombre_paciente = pacienteUser
                            ? `${pacienteUser.nombre ?? ''} ${pacienteUser.apellidos ?? ''}`.trim()
                            : 'Paciente desconocido';

                        const nombre_especialista = especialistaUser
                            ? `${especialistaUser.nombre ?? ''} ${especialistaUser.apellidos ?? ''}`.trim()
                            : 'Sin especialista';

                        return {
                            ...pacienteBase,
                            nombre_paciente,
                            nombre_especialista,
                        };
                    });

                    this.paginaActual = 1;
                },
                error: (err) => {
                    console.error('Error al obtener pacientes:', err);
                    this.error = 'Error al cargar pacientes';
                    this.huboError = true;
                    this.mostrarMensaje('Error al cargar pacientes', 'error');
                },
            });
    }

    cambiarRol(paciente: PacienteExtendido): void {
        const nombre = paciente.user?.nombre ?? 'Usuario';
        const apellidos = paciente.user?.apellidos ?? '';

        const snackBarRef = this.snackBar.open(
            `¿Dar de baja a ${nombre} ${apellidos}?`,
            'Confirmar',
            {
                duration: 5000,
                panelClass: ['snackbar-warning'],
                horizontalPosition: 'center',
                verticalPosition: 'top',
            }
        );

        snackBarRef.onAction().subscribe(() => {
            this.mostrarMensaje('Actualizando rol...', 'success');

            this.userService.updateRolUsuario(paciente.user_id).subscribe({
                next: () => {
                    this.mostrarMensaje(`${nombre} fue dado de baja correctamente`, 'success');
                    this.cargarPacientes();
                },
                error: () => {
                    this.mostrarMensaje(`Error al dar de baja a ${nombre}`, 'error');
                },
            });
        });
    }

    onFiltroChange() {
        this.paginaActual = 1;
    }

    // Filtro sobre TODO el dataset + orden actual + sin paginar (la tabla pagina)
    get pacientesFiltrados(): PacienteExtendido[] {
        const f = this.filtro.trim().toLowerCase();

        let filtrados = this.pacientes.filter((p) => {
            return (
                !f ||
                String(p.id).includes(f) ||
                (p.nombre_paciente?.toLowerCase() ?? '').includes(f) ||
                (p.numero_historial?.toLowerCase() ?? '').includes(f) ||
                (p.nombre_especialista?.toLowerCase() ?? '').includes(f) ||
                (p.ultima_cita?.especialista?.especialidad?.toLowerCase() ?? '').includes(f) ||
                (p.ultima_cita?.estado?.toLowerCase() ?? '').includes(f) ||
                (p.ultima_cita?.comentario?.toLowerCase() ?? '').includes(f)
            );
        });

        if (this.columnaOrden) {
            filtrados = filtrados.sort((a, b) => {
                const valA = this.obtenerValorOrden(a, this.columnaOrden!);
                const valB = this.obtenerValorOrden(b, this.columnaOrden!);
                if (typeof valA === 'string' && typeof valB === 'string') {
                    return this.direccionOrdenAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
                }
                if (valA < valB) return this.direccionOrdenAsc ? -1 : 1;
                if (valA > valB) return this.direccionOrdenAsc ? 1 : -1;
                return 0;
            });
        }

        return filtrados;
    }

    obtenerValorOrden(p: PacienteExtendido, columna: string): any {
        switch (columna) {
            case 'id':
                return p.id ?? 0;

            case 'nombre_paciente':
                return p.nombre_paciente.toLowerCase();

            case 'numero_historial':
                return (p.numero_historial ?? '').toLowerCase();

            case 'fecha_alta':
                return p.fecha_alta ? new Date(p.fecha_alta).getTime() : 0;

            case 'estado_cita':
                return (p.ultima_cita?.estado ?? '').toLowerCase();

            case 'comentario':
                return (p.ultima_cita?.comentario ?? '').toLowerCase();

            case 'nombre_especialista':
                return p.nombre_especialista.toLowerCase();

            case 'especialidad':
                return (p.ultima_cita?.especialista?.especialidad ?? '').toLowerCase();

            default:
                return '';
        }
    }

    ordenarPor(columna: string): void {
        if (this.columnaOrden === columna) {
            this.direccionOrdenAsc = !this.direccionOrdenAsc;
        } else {
            this.columnaOrden = columna;
            this.direccionOrdenAsc = true;
        }
    }

    cambiarPagina(pagina: number): void {
        this.paginaActual = pagina;
    }

    onPacienteCreado(_userId?: number) {
        this.cerrarModalNuevoPaciente();
        this.recargarPacientes();

        const url = this.router.url;
        this.router.navigateByUrl('/', { skipLocationChange: true })
            .then(() => this.router.navigateByUrl(url));
        //Fuerzo a que vuelva a hacer la solicitud de usuarios destruyendo el componente para volver a crearlo
    }

    abrirModalAsignarRolPaciente(): void {
        this.modalNuevoPacienteVisible = true;
    }

    cerrarModalNuevoPaciente(): void {
        this.modalNuevoPacienteVisible = false;
    }

    recargarPacientes(): void {
        this.cargarPacientes();
    }
}
