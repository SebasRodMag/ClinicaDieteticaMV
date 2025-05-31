import { Component, OnInit, ViewChild, TemplateRef, AfterViewInit } from '@angular/core';
import { Usuario } from '../models/usuario.model';
import { UserService } from '../service/User-Service/user.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { ModalEditUsuarioComponent } from './modal/modal-edit-usuario.component';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { MatSnackBar } from '@angular/material/snack-bar';


@Component({
    selector: 'app-usuarios-list',
    templateUrl: './usuarios-list.component.html',
    imports: [MatSnackBarModule, CommonModule, FormsModule, TablaDatosComponent, ModalEditUsuarioComponent],
})
export class UsuariosListComponent implements OnInit, AfterViewInit {
    usuarios: Usuario[] = [];
    usuariosFiltrados: Usuario[] = [];
    modalVisible: boolean = false;
    usuarioSeleccionado: Usuario = this.crearUsuarioVacio();
    esNuevoUsuario: boolean = false;

    filtro: string = '';
    loading: boolean = false;

    paginaActual = 1;
    itemsPorPagina = 10;
    maxPaginasVisibles = 5;

    columnaOrden: string | null = null;
    direccionOrdenAsc: boolean = true;

    columnas = ['id', 'nombre', 'apellidos', 'dni_usuario', 'email', 'acciones'];

    crearUsuarioVacio(): Usuario {
        return {
            id: 0,
            nombre: '',
            apellidos: '',
            dni_usuario: '',
            email: '',
            direccion: '',
            fecha_nacimiento: '',
            telefono: '',
            email_verified_at: null,
            created_at: null,
            updated_at: null,
            deleted_at: null
        };
    }
    @ViewChild('nombreTemplate', { static: true }) nombreTemplate!: TemplateRef<any>;
    @ViewChild('accionesTemplate', { static: true }) accionesTemplate!: TemplateRef<any>;

    templatesMap: { [key: string]: TemplateRef<any> } = {};

    constructor(private userService: UserService, private snackBar: MatSnackBar) { }

    ngOnInit() {
        this.cargarUsuarios();
    }

    ngAfterViewInit() {
        // Asigno el template sólo después de que ViewChild esté listo
        this.templatesMap = {
            nombre: this.nombreTemplate,
            acciones: this.accionesTemplate,
        };
    }

    mostrarMensaje(mensaje: string, tipo: 'success' | 'error') {
        this.snackBar.open(mensaje, 'Cerrar', {
            duration: 3000,
            panelClass: tipo === 'success' ? ['snackbar-' + tipo] : undefined,
        });
    }

    cargarUsuarios() {
        this.loading = true;
        this.userService.getUsuarios().subscribe({
            next: (data: Usuario[]) => {
                this.usuarios = data;
                this.filtrarUsuarios();
                this.loading = false;
                console.log('Se cargaron los usuarios');
            },
            error: () => {
                this.loading = false;
                console.warn('Error al cargar los usuario');
            }
        });
    }

    filtrarUsuarios() {
        if (!this.filtro) {
            this.usuariosFiltrados = [...this.usuarios];
        } else {
            const filtroLower = this.filtro.toLowerCase();
            this.usuariosFiltrados = this.usuarios.filter(u =>
                u.nombre.toLowerCase().includes(filtroLower) ||
                u.apellidos.toLowerCase().includes(filtroLower) ||
                u.dni_usuario.toLowerCase().includes(filtroLower) ||
                u.email.toLowerCase().includes(filtroLower)
            );
        }
        this.paginaActual = 1;
    }

    ordenarPor(columna: string) {
        if (this.columnaOrden === columna) {
            this.direccionOrdenAsc = !this.direccionOrdenAsc;
        } else {
            this.columnaOrden = columna;
            this.direccionOrdenAsc = true;
        }

        this.usuariosFiltrados.sort((a, b) => {
            let compA = a[columna as keyof Usuario];
            let compB = b[columna as keyof Usuario];

            if (compA == null) compA = '';
            if (compB == null) compB = '';

            if (typeof compA === 'string') compA = compA.toLowerCase();
            if (typeof compB === 'string') compB = compB.toLowerCase();

            if (compA < compB) return this.direccionOrdenAsc ? -1 : 1;
            if (compA > compB) return this.direccionOrdenAsc ? 1 : -1;
            return 0;
        });
    }

    cambiarPagina(nuevaPagina: number) {
        if (nuevaPagina >= 1 && nuevaPagina <= this.totalPaginas) {
            this.paginaActual = nuevaPagina;
        }
    }

    get totalPaginas(): number {
        return Math.ceil(this.usuariosFiltrados.length / this.itemsPorPagina);
    }

    eliminarUsuario(usuario: Usuario) {
        this.loading = true;
        this.userService.eliminarUsuario(usuario.id).subscribe({
            next: () => {
                this.cargarUsuarios();
                this.mostrarMensaje(`Usuario ${usuario.nombre} ${usuario.apellidos} eliminado correctamente.`, 'success');
                console.log(`Usuario ${usuario.nombre} ${usuario.apellidos} eliminado correctamente.`);
            },
            error: () => {
                this.loading = false;
                this.mostrarMensaje('Error al eliminar usuario.', 'error');
                console.warn(`Error al eliminar al usuario ${usuario.nombre} ${usuario.apellidos}.`);
            }
        });
    }

    editarUsuario(usuario: Usuario) {
        this.usuarioSeleccionado = { ...usuario }; // copia por seguridad
        this.esNuevoUsuario = false;
        this.modalVisible = true;
    }


    confirmarEliminarUsuario(usuario: Usuario) {
        const snackBarRef = this.snackBar.open(
            `¿Eliminar a ${usuario.nombre} ${usuario.apellidos}?`,
            'Eliminar',
            {
                duration: 5000,
                panelClass: ['snackbar-delete'],
                horizontalPosition: 'center',
                verticalPosition: 'top'
            }
        );

        snackBarRef.onAction().subscribe(() => {
            this.eliminarUsuario(usuario);
        });
    }

    nuevoUsuario() {
        this.usuarioSeleccionado = this.crearUsuarioVacio();
        this.esNuevoUsuario = true;
        this.modalVisible = true;
    }
    cerrarModal() {
        this.modalVisible = false;
        this.usuarioSeleccionado = this.crearUsuarioVacio();
    }

    guardarUsuario(usuario: Usuario) {
        if (usuario.id === 0) {
            this.userService.crearUsuario(usuario).subscribe({
                next: (nuevoUsuario) => {
                    this.cargarUsuarios();
                    this.mostrarMensaje('Usuario creado correctamente.', 'success');
                    console.log('Usuario creado correctamente.');
                },
                error: () => {
                    this.mostrarMensaje('Error al crear usuario.', 'error');
                    console.warn('Error al crear usuario.');
                }
            });
        } else {
            this.userService.actualizarUsuario(usuario).subscribe({
                next: () => {
                    this.cargarUsuarios();
                    this.mostrarMensaje('Usuario actualizado correctamente.', 'success');
                    console.log('Usuario actualizado correctamente.');
                },
                error: () => {
                    this.mostrarMensaje('Error al actualizar usuario.', 'error');
                    console.warn('Error al actualizar usuario.');
                }
            });
        }

    }
}
