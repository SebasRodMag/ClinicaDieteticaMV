import { Component, EventEmitter, Output, OnInit, Input, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { UserService } from '../../service/User-Service/user.service';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';

interface Usuario {
    id: number;
    nombre: string;
}

@Component({
    selector: 'app-modal-nuevo-especialista',
    standalone: true,
    templateUrl: './modal-nuevo-especialista.component.html',
    //styleUrls: ['./modal-nuevo-especialista.component.css'],
    imports: [CommonModule, FormsModule, MatSnackBarModule],
})
export class ModalNuevoEspecialistaComponent implements OnInit {
    @Input() modalVisible: boolean = true;
    @Output() creado = new EventEmitter<void>();
    @Output() cerrado = new EventEmitter<void>();

    usuarios: Usuario[] = [];
    usuariosFiltrados: Usuario[] = [];
    usuarioBusqueda: string = '';
    usuarioSeleccionado?: Usuario;

    especialidades: string[] = ['Endocrinología', 'Nutrición', 'Medicina General', 'Pediatría'];
    especialidadSeleccionada: string | null = null;

    mostrarLista: boolean = false;
    cargando: boolean = false;

    constructor(private userService: UserService, private snackBar: MatSnackBar) { }

    ngOnInit(): void {
        this.cargarUsuarios();
    }

    cargarUsuarios(): void {
        this.userService.getUsuariosSinRolEspecialistaNiPaciente().subscribe({
            next: (data) => {
                this.usuarios = data
                    .map((u) => ({ id: u.id, nombre: u.nombre_apellidos }))
                    .sort((a, b) => a.nombre.localeCompare(b.nombre));
                this.usuariosFiltrados = [...this.usuarios];
            },
            error: () => this.snackBar.open('Error al cargar usuarios', 'Cerrar', { duration: 3000 }),
        });
    }

    filtrarUsuarios(): void {
        const termino = this.usuarioBusqueda.toLowerCase().trim();
        this.usuariosFiltrados = this.usuarios.filter((u) =>
            u.nombre.toLowerCase().includes(termino)
        );
        this.mostrarLista = true;
    }

    seleccionarUsuario(usuario: Usuario): void {
        this.usuarioBusqueda = usuario.nombre;
        this.usuarioSeleccionado = usuario;
        this.mostrarLista = false;
    }

    confirmar(): void {
        if (!this.usuarioSeleccionado || !this.especialidadSeleccionada) {
            this.snackBar.open('Debe seleccionar un usuario y una especialidad.', 'Cerrar', {
                duration: 3000,
            });
            return;
        }

        this.cargando = true;

        this.userService
            .crearEspecialista({
                user_id: this.usuarioSeleccionado.id,
                especialidad: this.especialidadSeleccionada,
            })
            .subscribe({
                next: () => {
                    this.snackBar.open('Especialista creado correctamente', 'Cerrar', { duration: 3000 });
                    this.creado.emit();
                    this.cerrar();
                },
                error: () => {
                    this.snackBar.open('Error al crear especialista', 'Cerrar', { duration: 3000 });
                },
                complete: () => (this.cargando = false),
            });
    }

    cerrar(): void {
        this.cerrado.emit();
    }

    ocultarListaConDelay(): void {
        setTimeout(() => {
            this.mostrarLista = false;
        }, 200);
    }

    @HostListener('document:keydown.escape', ['$event'])
    handleEscapeKey(event: KeyboardEvent) {
        if (this.modalVisible) this.cerrar();
    }
}
