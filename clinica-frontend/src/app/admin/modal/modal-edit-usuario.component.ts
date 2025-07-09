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
    @Input() erroresExternos: { [campo: string]: string[] } = {};

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

        if (changes['erroresExternos'] && this.erroresExternos) {
            //Mapear arrays a string único por campo
            for (const campo in this.erroresExternos) {
                if (this.erroresExternos[campo]) {
                    this.errores[campo] = this.erroresExternos[campo].join('. ');
                }
            }
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
        const scriptRegex = /<script.*?>.*?<\/script>/gi;

        //Validar de campos obligatorios solo en cuando el modal se usa para crear un usuario
        if (this.esNuevo) {
            const camposObligatorios = ['nombre', 'apellidos', 'dni_usuario', 'email', 'password', 'password_confirmation'];
            for (const campo of camposObligatorios) {
                if (!this.usuarioForm[campo as keyof Usuario]) {
                    this.errores[campo] = 'Campo obligatorio';
                }
            }
        }

        //Validar de formato solo si hay contenido
        if (this.usuarioForm.dni_usuario && !dniRegex.test(this.usuarioForm.dni_usuario)) {
            this.errores['dni_usuario'] = 'DNI debe tener 8 dígitos y una letra';
        }

        if (this.usuarioForm.email && !emailRegex.test(this.usuarioForm.email)) {
            this.errores['email'] = 'Email no válido';
        }

        if (this.usuarioForm.telefono && !telefonoRegex.test(this.usuarioForm.telefono)) {
            this.errores['telefono'] = 'Teléfono no válido (debe comenzar por 6, 7, 8 o 9 y tener 9 dígitos)';
        }

        if (this.usuarioForm.direccion && scriptRegex.test(this.usuarioForm.direccion)) {
            this.errores['direccion'] = 'Contenido no permitido en dirección';
        }

        //Validar de contraseña
        if (this.esNuevo) {
            if (!this.usuarioForm.password || !this.usuarioForm.password_confirmation) {
                this.errores['password'] = 'Debe introducir y confirmar la contraseña';
            }
        } else {
            //Solo si está editando y solo rellena uno de los dos campos
            if ((this.usuarioForm.password && !this.usuarioForm.password_confirmation) ||
                (!this.usuarioForm.password && this.usuarioForm.password_confirmation)) {
                this.mostrarMensaje('Debe rellenar ambos campos de contraseña para cambiarla', 'error');
                return;
            }
        }

        //Confirmar coincidencia de contraseña si se han rellenado ambas
        if (this.usuarioForm.password &&
            this.usuarioForm.password_confirmation &&
            this.usuarioForm.password !== this.usuarioForm.password_confirmation) {
            this.errores['password_confirmation'] = 'Las contraseñas no coinciden';
        }

        //Mostrar errores
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

    esFormularioValido(): boolean {
        const campos: (keyof Usuario)[] = ['nombre', 'apellidos', 'dni_usuario', 'email'];
        if (this.esNuevo) {
            campos.push('password', 'password_confirmation');
        }

        //Verificar campos obligatorios
        for (const campo of campos) {
            if (!this.usuarioForm[campo]) return false;
            if (this.errores[campo]) return false;
        }

        //Validación de contraseñas
        if (this.usuarioForm.password !== this.usuarioForm.password_confirmation) return false;

        return true;
    }

    validarCampo(campo: keyof Usuario): void {
        const valor = this.usuarioForm[campo];
        this.errores[campo] = '';

        if (!this.esNuevo && ((campo === 'password' && this.usuarioForm.password && !this.usuarioForm.password_confirmation) ||
            (campo === 'password_confirmation' && this.usuarioForm.password_confirmation && !this.usuarioForm.password))) {
            this.errores['password'] = 'Debe rellenar ambos campos de contraseña para cambiarla';
            return;
        }

        if (campo === 'dni_usuario' && valor && !/^[0-9]{8}[A-Za-z]$/.test(String(valor))) {
            this.errores[campo] = 'DNI debe tener 8 dígitos y una letra';
        }

        if (campo === 'email' && valor && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(valor))) {
            this.errores[campo] = 'Email no válido';
        }

        if (campo === 'telefono' && valor && !/^[6789]\d{8}$/.test(String(valor))) {
            this.errores[campo] = 'Teléfono no válido';
        }

        if (campo === 'direccion' && valor && /<script.*?>.*?<\/script>/gi.test(String(valor))) {
            this.errores[campo] = 'Contenido no permitido en dirección';
        }

        if (campo === 'password' || campo === 'password_confirmation') {
            const pass = this.usuarioForm.password;
            const confirm = this.usuarioForm.password_confirmation;

            if (this.esNuevo && (!pass || !confirm)) {
                this.errores['password'] = 'Debe introducir y confirmar la contraseña';
                return;
            }

            if (pass && confirm && pass !== confirm) {
                this.errores['password_confirmation'] = 'Las contraseñas no coinciden';
            }
        }
    }


}
