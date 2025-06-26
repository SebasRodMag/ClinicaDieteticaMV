import {Component, OnInit,ViewChild,TemplateRef,AfterViewInit,} from '@angular/core';
import { CommonModule } from '@angular/common';
import { Paciente } from '../models/paciente.model';
import { UserService } from '../service/User-Service/user.service';
import { finalize } from 'rxjs';
import { ToastrService } from 'ngx-toastr';
import { FormsModule } from '@angular/forms';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';

@Component({
    selector: 'app-pacientes-list',
    standalone: true,
    imports: [MatSnackBarModule, CommonModule, FormsModule, TablaDatosComponent],
    templateUrl: './pacientes-list.component.html',
})
export class PacientesListComponent implements OnInit, AfterViewInit {
    pacientes: Paciente[] = [];
    loading = true;
    huboError: boolean = false;
    error = '';

    filtro = '';
    columnaOrden: string | null = null;
    direccionOrdenAsc = true;
    itemsPorPagina = 10;
    paginaActual = 1;
    maxPaginasVisibles = 5;

    columnas: string[] = ['id', 'nombre_paciente', 'fecha_alta', 'estado_cita', 'comentario', 'nombre_especialista', 'especialidad', 'acciones'];

    templatesMap: { [key: string]: TemplateRef<any> } = {};

    @ViewChild('especialistaTemplate') especialistaTemplate!: TemplateRef<any>;
    @ViewChild('accionesTemplate') accionesTemplate!: TemplateRef<any>;
    @ViewChild('nombrePacienteTemplate') nombrePacienteTemplate!: TemplateRef<any>;
    @ViewChild('estadoCitaTemplate') estadoCitaTemplate!: TemplateRef<any>;
    @ViewChild('comentarioTemplate') comentarioTemplate!: TemplateRef<any>;
    @ViewChild('nombreEspecialistaTemplate') nombreEspecialistaTemplate!: TemplateRef<any>;
    @ViewChild('especialidadTemplate') especialidadTemplate!: TemplateRef<any>;

    constructor(private userService: UserService, private toastr: ToastrService, private snackBar: MatSnackBar) { }

    ngOnInit(): void {
        this.cargarPacientes();
    }

    ngAfterViewInit(): void {
        this.templatesMap = {
            nombre_paciente: this.nombrePacienteTemplate,
            estado_cita: this.estadoCitaTemplate,
            comentario: this.comentarioTemplate,
            nombre_especialista: this.nombreEspecialistaTemplate,
            especialidad: this.especialidadTemplate,
            acciones: this.accionesTemplate,
        };
    }

    mostrarMensaje(mensaje: string, tipo: 'success' | 'error') {
        this.snackBar.open(mensaje, 'Cerrar', {
            duration: 3000,
            panelClass: tipo === 'success' ? ['snackbar-' + tipo] : undefined,
        });
    }

    cargarPacientes(): void {
        this.loading = true;
        this.error = '';
        this.huboError = false;
        this.userService.pacientesConEspecialista()
        .pipe(finalize(() => this.loading = false))
        .subscribe({
            next: (data) => {
                console.log('Pacientes recibidos:', data.length);
                this.pacientes = data;
                this.loading = false;
            },
            error: (err) => {
                console.error('Error al obtener especialistas:', err);
                this.error = 'Error al cargar pacientes';
                this.loading = false;
                this.huboError = true;
                this.snackBar.open('Error al cargar especialistas', 'Cerrar', { duration: 3000 });
            },
        });
    }

    cambiarRol(paciente: Paciente): void {

        const nombre = paciente.usuario?.nombre ?? 'Usuario';
        const apellidos = paciente.usuario?.apellidos ?? '';

        const snackBarRef = this.snackBar.open(
            `¿Estás seguro de que deseas dar de baja a ${nombre} ${apellidos}?`,
            'Confirmar',
            { duration: 5000 }
        );

        snackBarRef.onAction().subscribe(() => {
            this.toastr.info('Actualizando rol...', '', { disableTimeOut: true });

            this.userService.updateRolUsuario(paciente.user_id).subscribe({
                next: () => {
                    this.toastr.clear();
                    this.toastr.success(`${nombre} fue dado de baja correctamente`);
                    this.cargarPacientes();
                },
                error: () => {
                    this.toastr.clear();
                    this.toastr.error(`Error al dar de baja a ${nombre}`);
                },
            });
        });

    }

    obtenerValorOrden(obj: any, columna: string): any {
        switch (columna) {
            case 'id':
                return obj.id;
            case 'numero_historial':
                return obj.numero_historial?.toLowerCase() ?? '';
            case 'fecha_alta':
                return new Date(obj.fecha_alta).getTime();
            case 'fecha_baja':
                return obj.fecha_baja ? new Date(obj.fecha_baja).getTime() : 0;
            case 'especialista':
                return obj.especialista?.usuario?.nombre.toLowerCase() ?? '';
            default:
                return '';
        }
    }

    get pacientesFiltrados(): Paciente[] {
        const filtroLower = this.filtro.toLowerCase();

        let filtrados = this.pacientes.filter((p) => {
            const nombreEspecialista = p.especialista?.usuario?.nombre.toLowerCase() ?? '';
            const apellidosEspecialista = p.especialista?.usuario?.apellidos.toLowerCase() ?? '';
            return (
                nombreEspecialista.includes(filtroLower) ||
                apellidosEspecialista.includes(filtroLower) ||
                p.numero_historial.toLowerCase().includes(filtroLower)
            );
        });

        if (this.columnaOrden) {
            filtrados = filtrados.sort((a, b) => {
                const valA = this.obtenerValorOrden(a, this.columnaOrden!);
                const valB = this.obtenerValorOrden(b, this.columnaOrden!);
                if (valA < valB) return this.direccionOrdenAsc ? -1 : 1;
                if (valA > valB) return this.direccionOrdenAsc ? 1 : -1;
                return 0;
            });
        }

        return filtrados;
    }

    get pacientesFiltradosPaginados(): Paciente[] {
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
        return this.pacientesFiltrados.slice(inicio, inicio + this.itemsPorPagina);
    }

    get totalPaginas(): number {
        return Math.ceil(this.pacientesFiltrados.length / this.itemsPorPagina);
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
}
