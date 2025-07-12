import { Component, OnInit, ViewChild, TemplateRef, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { Log } from '../models/log.model';
import { UserService } from '../service/User-Service/user.service';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { finalize } from 'rxjs';
import { formatearFecha, formatearHora } from '../components/utilidades/sanitizar.utils';

@Component({
    selector: 'app-logs-list',
    standalone: true,
    templateUrl: './logs-list.component.html',
    imports: [CommonModule, FormsModule, TablaDatosComponent, MatSnackBarModule],
})
export class LogsListComponent implements OnInit, AfterViewInit {

    logs: Log[] = [];
    loading: boolean = false;
    huboError: boolean = false;
    filtro: string = '';

    columnas: string[] = ['usuario', 'email', 'accion', 'tabla_afectada', 'registro_id', 'created_at'];

    columnaOrden: string | null = null;
    direccionOrdenAsc: boolean = true;

    itemsPorPagina = 10;
    paginaActual = 1;
    maxPaginasVisibles = 5;

    @ViewChild('usuarioTemplate') usuarioTemplate!: TemplateRef<any>;
    @ViewChild('emailTemplate') emailTemplate!: TemplateRef<any>;
    @ViewChild('accionTemplate') accionTemplate!: TemplateRef<any>;
    @ViewChild('tablaTemplate') tablaTemplate!: TemplateRef<any>;
    @ViewChild('registroTemplate') registroTemplate!: TemplateRef<any>;
    @ViewChild('fechaTemplate') fechaTemplate!: TemplateRef<any>;

    templatesMap: { [key: string]: TemplateRef<any> } = {};
    filtroFecha: string = '';

    constructor(private userService: UserService, private snackBar: MatSnackBar) { }

    ngOnInit(): void {
        const hoy = new Date();
        this.filtroFecha = hoy.toISOString().split('T')[0]; //Establecemos la fecha de hoy en el filtro de búsqueda en formato YYYY-MM-DD
        this.cargarLogs();
    }

    ngAfterViewInit(): void {
        this.templatesMap = {
            usuario: this.usuarioTemplate,
            email: this.emailTemplate,
            accion: this.accionTemplate,
            tabla_afectada: this.tablaTemplate,
            registro_id: this.registroTemplate,
            created_at: this.fechaTemplate,
        };
    }

    cargarLogs(): void {
        this.loading = true;
        this.huboError = false;

        this.userService.obtenerLogs()
            .pipe(finalize(() => this.loading = false))
            .subscribe({
                next: (logs: Log[]) => {
                    this.logs = logs;
                },
                error: (err) => {
                    console.error('Error al cargar logs:', err);
                    this.huboError = true;
                    this.snackBar.open('Error al cargar logs', 'Cerrar', { duration: 3000 });
                }
            });
    }

    get logsFiltrados(): Log[] {
        const filtroLower = this.filtro.toLowerCase();

        let filtrados = this.logs.filter(log => {
            const usuario = `${log.user?.nombre ?? ''} ${log.user?.apellidos ?? ''}`.toLowerCase();
            const email = log.user?.email?.toLowerCase() ?? '';
            const accion = log.accion.toLowerCase();
            const tabla = log.tabla_afectada?.toLowerCase() ?? '';

            const coincideTexto = usuario.includes(filtroLower)
                || email.includes(filtroLower)
                || accion.includes(filtroLower)
                || tabla.includes(filtroLower);

            const coincideFecha = this.filtroFecha
                ? log.created_at.startsWith(this.filtroFecha)
                : true;

            return coincideTexto && coincideFecha;
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

    get logsFiltradosPaginados(): Log[] {
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
        return this.logsFiltrados.slice(inicio, inicio + this.itemsPorPagina);
    }

    get totalPaginas(): number {
        return Math.ceil(this.logsFiltrados.length / this.itemsPorPagina);
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

    obtenerValorOrden(log: Log, columna: string): any {
        switch (columna) {
            case 'usuario':
                return `${log.user?.nombre ?? ''} ${log.user?.apellidos ?? ''}`.toLowerCase();
            case 'email':
                return log.user?.email?.toLowerCase() ?? '';
            case 'accion':
                return log.accion.toLowerCase();
            case 'tabla_afectada':
                return log.tabla_afectada?.toLowerCase() ?? '';
            case 'registro_id':
                return log.registro_id ?? '';
            case 'created_at':
                return new Date(log.created_at).getTime();
            default:
                return '';
        }
    }

    /**
     * Formatea fecha y hora tipo '2025-07-10 14:30:00' a '10/07/2025 14:30'
     */
    formatearFechaHora(fechaIso: string): string {
        return `${formatearFecha(fechaIso)} ${formatearHora(fechaIso)}`;
    }

    aplicarFiltros(): void {
        this.paginaActual = 1;
    }

    cambiarDia(dias: number): void {
        const fecha = this.filtroFecha ? new Date(this.filtroFecha) : new Date();
        fecha.setDate(fecha.getDate() + dias);
        this.filtroFecha = fecha.toISOString().split('T')[0]; // YYYY-MM-DD
        this.aplicarFiltros();
    }
}
