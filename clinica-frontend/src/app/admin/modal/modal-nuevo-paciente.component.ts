import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { UsuarioDisponible } from '../../models/usuarioDisponible.model';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { UserService } from '../../service/User-Service/user.service';
import { MatSnackBar } from '@angular/material/snack-bar';
import { finalize } from 'rxjs';

@Component({
    selector: 'app-modal-nuevo-paciente',
    standalone: true,
    imports: [CommonModule, FormsModule, MatSnackBarModule],
    templateUrl: './modal-nuevo-paciente.component.html'
})
export class ModalNuevoPacienteComponent implements OnInit {
    @Output() cerrar = new EventEmitter<void>();
    @Output() pacienteCreado = new EventEmitter<number>();

    @Input() excluirUserIds: number[] = []; //ID de usuario a excluir


    @Input() set visible(v: boolean) {
        if (v && !this._visible) {
            this.resetearEstado();
            this.cargando = true;
            this.cargarUsuariosDisponibles(true);
        }
        this._visible = v;
    }

    private _visible = false;
    get visible(): boolean { return this._visible; }

    private _filtroUsuario: string = '';

    usuariosDisponibles: UsuarioDisponible[] = [];
    user_idSeleccionado: number | null = null;
    cargando = false;
    mensajeErrorBusqueda: string = '';
    //filtro el usuario seleccionado de la lista hasta que se recargue la página
    trackById(_index: number, item: UsuarioDisponible) { return item.id; }

    constructor(
        private userService: UserService,
        private snackBar: MatSnackBar
    ) { }

    private resetearEstado() {
        this.user_idSeleccionado = null;
        this._filtroUsuario = '';
        this.mensajeErrorBusqueda = '';
        this.cargando = false;
    }

    ngOnInit(): void { }



    cargarUsuariosDisponibles(noCache = false): void {
        this.cargando = true;
        this.userService.getUsuariosSinRolEspecialistaNiPaciente(noCache).pipe(finalize(() => { this.cargando = false; })).subscribe({
            next: (res) => {
                // Filtra lo que venga del backend con la lista del padre
                const filtrados = (res.data ?? []).filter(u => !this.excluirUserIds.includes(u.id));
                this.usuariosDisponibles = filtrados.sort((a, b) => a.nombre_apellidos.localeCompare(b.nombre_apellidos));
            },
            error: (error) => {
                console.error('Error al cargar usuarios:', error);
                this.snackBar.open('Error al cargar usuarios', 'Cerrar', {
                    duration: 4000,
                    panelClass: ['snackbar-error']
                });
                this.cargando = false;
            }
        });
    }

    guardar(): void {
        console.log('user_idSeleccionado:', this.user_idSeleccionado);
        const usuario = this.usuariosDisponibles.find(u => u.id === Number(this.user_idSeleccionado));


        if (!usuario) {
            this.snackBar.open('Debe seleccionar un usuario válido', 'Cerrar', {
                duration: 3000,
                panelClass: ['snackbar-warning']
            });
            return;
        }

        const mensaje = `¿Asignar el rol Paciente a ${usuario.nombre_apellidos} (ID: ${usuario.id})?`;

        const snackBarRef = this.snackBar.open(mensaje, 'Confirmar', {
            duration: 6000,
            panelClass: ['snackbar-warning'],
            horizontalPosition: 'center',
            verticalPosition: 'top'
        });

        snackBarRef.onAction().subscribe(() => {
            const paciente = { user_id: usuario.id };
            this.cargando = true;

            this.userService.crearPaciente(paciente).subscribe({
                next: () => {
                    //quitamos al usuario seleccionado del array
                    this.usuariosDisponibles = this.usuariosDisponibles.filter(u => u.id !== usuario.id);
                    this.user_idSeleccionado = null;
                    this.snackBar.open('Paciente creado correctamente', 'Cerrar', {
                        duration: 3000,
                        panelClass: ['snackbar-success']
                    });
                    this.pacienteCreado.emit(usuario.id);
                    this.cerrarModal();
                },
                error: (error) => {
                    console.error('Error al crear paciente:', error);
                    this.snackBar.open('Error al crear el paciente', 'Cerrar', {
                        duration: 4000,
                        panelClass: ['snackbar-error']
                    });
                    this.cargando = false;
                }
            });
        });
    }

    cerrarModal(): void {
        this.visible = false;
        this.user_idSeleccionado = null;
        this.cargando = false;
        this._filtroUsuario = '';
        this.mensajeErrorBusqueda = '';
        this.cerrar.emit();
    }

    get usuariosFiltrados(): UsuarioDisponible[] {
        const filtro = this._filtroUsuario.trim().toLowerCase();
        return this.usuariosDisponibles
            .filter(u => !this.excluirUserIds.includes(u.id))
            .filter(u =>
                u.nombre_apellidos.toLowerCase().includes(filtro) ||
                u.id.toString().includes(filtro)
            );
    }

    get filtroUsuario(): string {
        return this._filtroUsuario;
    }

    set filtroUsuario(valor: string) {
        this._filtroUsuario = valor.trim();
        this.mensajeErrorBusqueda = '';

        if (!valor) {
            this.user_idSeleccionado = null;
            return;
        }

        const id = Number(valor);
        if (!isNaN(id) && id > 0) {
            const usuario = this.usuariosDisponibles.find(u => u.id === id);
            if (usuario) {
                this.user_idSeleccionado = usuario.id;
            } else {
                this.user_idSeleccionado = null;
                this.mensajeErrorBusqueda = `No se encontró ningún usuario con ID: ${id}`;
            }
        } else {
            this.user_idSeleccionado = null;
            this.mensajeErrorBusqueda = `Debe ingresar un ID válido (número positivo)`;
        }
    }
}
