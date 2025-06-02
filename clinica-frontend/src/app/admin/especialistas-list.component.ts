import { Component, OnInit, TemplateRef, ViewChild } from '@angular/core';
import { UserService } from '../service/User-Service/user.service';
import { EspecialistaList } from '../models/especialistaList.model';
import { finalize } from 'rxjs';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { FormsModule } from '@angular/forms';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { ModalNuevoEspecialistaComponent } from './modal/modal-nuevo-especialista.component';
import { CommonModule } from '@angular/common';

@Component({
    selector: 'app-especialistas-list',
    standalone: true,
    templateUrl: './especialistas-list.component.html',
    imports: [CommonModule, MatSnackBarModule, FormsModule, TablaDatosComponent, ModalNuevoEspecialistaComponent],
})
export class EspecialistasListComponent implements OnInit {
    especialistas: EspecialistaList[] = [];
    especialistasFiltrados: EspecialistaList[] = [];

    loading: boolean = false;

    filtro: string = '';
    columnaOrden: string = 'nombre';
    direccionOrdenAsc: boolean = true;

    paginaActual: number = 1;
    itemsPorPagina: number = 10;
    maxPaginasVisibles: number = 5;

    columnas: string[] = ['nombre', 'email', 'especialidad', 'acciones'];
    templatesMap: { [key: string]: TemplateRef<any> } = {};

    modalVisible: boolean = false;
    especialistaSeleccionado: EspecialistaList | null = null;
    esNuevoEspecialista: boolean = false;

    @ViewChild('nombreTemplate', { static: true }) nombreTemplate!: TemplateRef<any>;
    @ViewChild('accionesTemplate', { static: true }) accionesTemplate!: TemplateRef<any>;

    constructor(private userService: UserService, private snackBar: MatSnackBar) { this.cargarEspecialistas(); }

    ngOnInit(): void {
        this.templatesMap = {
            nombre: this.nombreTemplate,
            acciones: this.accionesTemplate,
        };

        this.cargarEspecialistas();
    }

    mostrarMensaje(mensaje: string, tipo: 'success' | 'error') {
        this.snackBar.open(mensaje, 'Cerrar', {
            duration: 3000,
            panelClass: tipo === 'success' ? ['snackbar-' + tipo] : undefined,
        });
    }

    cargarEspecialistas(): void {
        this.loading = true;
        this.userService.getListarEspecialistas().pipe(finalize(() => this.loading = false)).subscribe({
            next: (data) => {
                console.log('Especialistas recibidos:', data.length);
                this.especialistas = data;
                this.filtrarEspecialistas();
            },
            error: (err) => {
                console.error('Error al obtener especialistas:', err);
                this.snackBar.open('Error al cargar especialistas', 'Cerrar', { duration: 3000 });
            },
        });
    }

    filtrarEspecialistas(): void {
        const termino = this.filtro.toLowerCase().trim();

        this.especialistasFiltrados = this.especialistas.filter((esp) => {
            return (
                esp.nombre_apellidos.toLowerCase().includes(termino) ||
                esp.email.toLowerCase().includes(termino) ||
                (esp.especialidad || '').toLowerCase().includes(termino)
            );
        });

        this.paginaActual = 1;
    }

    ordenarPor(columna: string): void {
        if (this.columnaOrden === columna) {
            this.direccionOrdenAsc = !this.direccionOrdenAsc;
        } else {
            this.columnaOrden = columna;
            this.direccionOrdenAsc = true;
        }

        this.especialistasFiltrados.sort((a, b) => {
            let valorA: string = this.obtenerValorColumna(a, columna);
            let valorB: string = this.obtenerValorColumna(b, columna);

            return this.direccionOrdenAsc ? valorA.localeCompare(valorB) : valorB.localeCompare(valorA);
        });
    }

    obtenerValorColumna(esp: EspecialistaList, columna: string): string {
        switch (columna) {
            case 'nombre':
                return esp.nombre_apellidos;
            case 'email':
                return esp.email;
            case 'especialidad':
                return esp.especialidad || '';
            default:
                return '';
        }
    }

    cambiarPagina(pagina: number): void {
        this.paginaActual = pagina;
    }

    confirmarDarDeBaja(userId: number): void {
        const snackBarRef = this.snackBar.open(
            `¿Seguro que deseas dar de baja al especialista?`,
            'Confirmar',
            { duration: 5000 }
        );

        snackBarRef.onAction().subscribe(() => {
            this.darDeBaja(userId);
        });
    }

    darDeBaja(userId: number): void {
        this.userService.updateRolUsuario(userId).subscribe({
            next: () => {
                this.mostrarMensaje(`Especialista dado de baja correctamente.`, 'success');
                this.cargarEspecialistas();
            },
            error: (err) => {
                console.error('Error al dar de baja:', err);
                this.mostrarMensaje(`Hubo un error al dar de baja al especialista.`, 'error');
            },
        });
    }

    nuevoEspecialista(): void {
        this.esNuevoEspecialista = true;
        this.especialistaSeleccionado = null;
        this.modalVisible = true;
    }

    cerrarModal(): void {
        this.modalVisible = false;
        this.especialistaSeleccionado = null;
    }

    guardarEspecialista(esp: EspecialistaList): void {
        this.modalVisible = false;
        this.cargarEspecialistas();
    }
}
