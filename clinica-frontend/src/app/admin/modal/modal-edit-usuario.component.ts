import { Component, EventEmitter, Input, Output, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Usuario } from '../../models/usuario.model';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
    selector: 'app-modal-edit-usuario',
    standalone: true,
    imports: [MatSnackBarModule, CommonModule, FormsModule],
    templateUrl: './modal-edit-usuario.component.html',
})
export class ModalEditUsuarioComponent implements OnChanges {
    @Input() visible: boolean = false;
    @Input() usuario: Usuario = this.nuevoUsuario();
    @Input() esNuevo: boolean = false;

    @Output() cerrar = new EventEmitter<void>();
    @Output() guardar = new EventEmitter<Usuario>();


    usuarioForm!: Usuario;
    errores: { [campo: string]: string } = {};

    constructor(private snackBar: MatSnackBar) { }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['usuario'] || changes['esNuevo']) {
            this.usuarioForm = this.esNuevo
                ? this.nuevoUsuario()
                : { ...this.usuario }; //copia del usuario para no modificar el original directamente
            this.errores = {};
        }
    }


    mostrarMensaje(mensaje: string, tipo: 'success' | 'error') {
        this.snackBar.open(mensaje, 'Cerrar', {
            duration: 3000,
            panelClass: tipo === 'success' ? ['snackbar-' + tipo] : undefined,
        });
    }

    onSubmit() {

        this.errores = {};

        const dniRegex = /^[0-9]{8}[A-Za-z]$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const telefonoRegex = /^[6789]\d{8}$/;

        // Validación general al crear usuario
        if (this.esNuevo) {
            const camposObligatorios = [
                'nombre',
                'apellidos',
                'dni_usuario',
                'email',
                'direccion',
                'fecha_nacimiento',
                'telefono',
                'password',
                'password_confirmation'
            ];

            for (const campo of camposObligatorios) {
                if (!this.usuarioForm[campo as keyof Usuario]) {
                    this.errores[campo] = 'Campo obligatorio';
                }
            }

            if (this.errores['dni_usuario'] === undefined && !dniRegex.test(this.usuarioForm.dni_usuario)) {
                this.errores['dni_usuario'] = 'DNI debe tener 8 dígitos y una letra';
            }

            if (this.errores['email'] === undefined && !emailRegex.test(this.usuarioForm.email)) {
                this.errores['email'] = 'Email no válido';
            }

            if (this.errores['telefono'] === undefined && !telefonoRegex.test(this.usuarioForm.telefono!)) {
                this.errores['telefono'] = 'Teléfono no válido';
            }

            if (this.usuarioForm.password !== this.usuarioForm.password_confirmation) {
                this.errores['password_confirmation'] = 'Las contraseñas no coinciden';
            }

            if (Object.keys(this.errores).length > 0) {
                this.mostrarMensaje('Hay errores en el formulario', 'error');
                return;
            }
        }

        // Validación de contraseña si se está modificando
        if ((this.usuarioForm.password && !this.usuarioForm.password_confirmation) ||
            (!this.usuarioForm.password && this.usuarioForm.password_confirmation)) {
            this.mostrarMensaje('Debe rellenar ambos campos de contraseña para cambiarla', 'error');
            return;
        }

        if (
            this.usuarioForm.password &&
            this.usuarioForm.password_confirmation &&
            this.usuarioForm.password !== this.usuarioForm.password_confirmation
        ) {
            this.mostrarMensaje('Las contraseñas no coinciden', 'error');
            return;
        }

        // Validación de formato de teléfono en edición (si se modifica)
        if (this.usuarioForm.telefono && !telefonoRegex.test(this.usuarioForm.telefono)) {
            this.mostrarMensaje('El teléfono debe tener 9 dígitos y empezar por 6, 7, 8 o 9', 'error');
            return;
        }

        // Validación de formato de DNI en edición (si se modifica)
        if (this.usuarioForm.dni_usuario && !dniRegex.test(this.usuarioForm.dni_usuario)) {
            this.mostrarMensaje('El DNI debe tener 8 dígitos seguidos de una letra (ej. 12345678Z)', 'error');
            return;
        }

        // Validación de formato de email en edición (si se modifica)
        if (this.usuarioForm.email && !emailRegex.test(this.usuarioForm.email)) {
            this.mostrarMensaje('El email no tiene un formato válido', 'error');
            return;
        }

        if (Object.keys(this.errores).length > 0) {
            this.mostrarMensaje('Hay errores en el formulario', 'error');
            return;
        }

        this.guardar.emit({ ...this.usuarioForm });
    }

    private nuevoUsuario(): Usuario {
        return {
            id: 0,
            nombre: '',
            apellidos: '',
            dni_usuario: '',
            email: '',
            direccion: '',
            fecha_nacimiento: '',
            telefono: '',
            password: '',
            password_confirmation: '',
            email_verified_at: null,
            created_at: null,
            updated_at: null,
            deleted_at: null
        };
    }

    cerrarModal(): void {
        this.cerrar.emit();
        this.errores = {};
    }

    
}
