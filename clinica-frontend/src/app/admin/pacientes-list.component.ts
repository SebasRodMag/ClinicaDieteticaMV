import {
    Component,
    OnInit,
    ViewChild,
    TemplateRef,
    AfterViewInit,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { Paciente } from '../models/paciente.model';
import { Cita } from '../models/cita.model';
import { UserService } from '../service/User-Service/user.service';
import { ToastrService } from 'ngx-toastr';
import { FormsModule } from '@angular/forms';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';

@Component({
    selector: 'app-pacientes-list',
    standalone: true,
    imports: [CommonModule, TablaDatosComponent, FormsModule],
    templateUrl: './pacientes-list.component.html',
})
export class PacientesListComponent implements OnInit, AfterViewInit {
    pacientes: Paciente[] = [];
    loading = true;
    error = '';

    filtro = '';
    columnaOrden: string | null = null;
    direccionOrdenAsc = true;
    itemsPorPagina = 10;
    paginaActual = 1;

    columnas: string[] = ['id', 'numero_historial', 'fecha_alta', 'fecha_baja', 'especialista', 'acciones'];

    templatesMap: { [key: string]: TemplateRef<any> } = {};

    @ViewChild('especialistaTemplate') especialistaTemplate!: TemplateRef<any>;
    @ViewChild('accionesTemplate') accionesTemplate!: TemplateRef<any>;

    constructor(private userService: UserService, private toastr: ToastrService) { }

    ngOnInit(): void {
        this.cargarPacientes();
    }

    ngAfterViewInit(): void {
        this.templatesMap = {
            especialista: this.especialistaTemplate,
            acciones: this.accionesTemplate,
        };
    }

    cargarPacientes(): void {
        this.loading = true;
        this.error = '';
        this.userService.pacientesConEspecialista().subscribe({
            next: (data) => {
                this.pacientes = data;
                this.loading = false;
            },
            error: (err) => {
                this.error = 'Error al cargar pacientes';
                this.loading = false;
                console.error(err);
            },
        });
    }

    cambiarRol(paciente: Paciente): void {
        if (
            paciente.especialista &&
            paciente.especialista.usuario &&
            confirm(`¿Dar de baja a ${paciente.especialista.usuario.nombre} ${paciente.especialista.usuario.apellidos}?`)
        ) {
            this.toastr.info('Actualizando rol...', '', { disableTimeOut: true });
            this.userService.updateRolUsuario(paciente.id, 'usuario').subscribe({
                next: () => {
                    this.toastr.clear();
                    this.toastr.success(`${paciente.especialista!.usuario!.nombre} fue dado de baja correctamente`);
                    this.cargarPacientes();
                },
                error: () => {
                    this.toastr.clear();
                    this.toastr.error(`Error al dar de baja a ${paciente.especialista!.usuario!.nombre}`);
                },
            });
        }
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

    get paginas(): number[] {
        return Array.from({ length: this.totalPaginas }, (_, i) => i + 1);
    }
}
