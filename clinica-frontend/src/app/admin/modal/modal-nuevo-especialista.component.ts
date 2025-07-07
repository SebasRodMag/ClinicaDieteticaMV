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

    especialidades: string[] = [];
    especialidadSeleccionada: string | null = null;

    mostrarLista: boolean = false;
    cargando: boolean = true;
    guardando: boolean = false;
    private pendientesPorCargar = 2;

    constructor(private userService: UserService, private snackBar: MatSnackBar) { }

    ngOnInit(): void {
        this.cargarUsuarios();
        this.obtenerEspecialidades();
        this.cargarUsuarios();
        this.obtenerEspecialidades();
    }

    cargarUsuarios(): void {
        this.userService.getUsuariosSinRolEspecialistaNiPaciente().subscribe({
            next: (response) => {
                const usuarios = response.data;
                this.usuarios = usuarios
                    .map((u) => ({ id: u.id, nombre: u.nombre_apellidos }))
                    .sort((a, b) => a.nombre.localeCompare(b.nombre));
                this.usuariosFiltrados = [...this.usuarios];
                if (this.usuarios.length === 0) {
                    console.warn('No hay usuarios disponibles');
                    this.snackBar.open('No hay usuarios disponibles para crear especialistas', 'Cerrar', {
                        duration: 3000,
                    });
                } else {
                    console.log('Usuarios cargados:', this.usuarios.length);
                }
                this.marcarCargaCompletada();
            },
            error: () =>{
                this.snackBar.open('Error al cargar usuarios', 'Cerrar', { duration: 3000 });
                this.marcarCargaCompletada();
            }
                
                
        });
    }

    obtenerEspecialidades(): void {
        this.userService.getConfiguracion().subscribe({
            next: (response) => {
                const especialidades = response.configuraciones?.['Especialidades'];

                if (Array.isArray(especialidades)) {
                    this.especialidades = especialidades;
                    console.log('Especialidades cargadas:', this.especialidades.length);
                } else {
                    console.warn('No se encontraron especialidades en la configuración');
                    this.snackBar.open('No se encontraron especialidades en la configuración', 'Cerrar', {
                        duration: 3000,
                    });
                }
                this.marcarCargaCompletada();
            },
            error: () => {
                this.snackBar.open('Error al obtener especialidades', 'Cerrar', { duration: 3000 });
                this.marcarCargaCompletada();
            },
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

        this.guardando = true;

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
                complete: () => (this.guardando = false),
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
    //Para mostrar el modal, solo cuando ya se cargaron los datos
    //mientras tanto, se muestra un spinner.
    private marcarCargaCompletada(): void {
        this.pendientesPorCargar--;
        if (this.pendientesPorCargar <= 0) {
            this.cargando = false;
        }
    }

}
