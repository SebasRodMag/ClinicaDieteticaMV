import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Paciente } from '../../models/paciente.model';
import { Usuario } from '../../models/usuario.model';

@Component({
    selector: 'app-paciente-edit',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: 'paciente-edit.component.html',
})
export class PacienteEditComponent implements OnInit {
    @Input() paciente!: Paciente;
    @Input() usuario!: Usuario;
    @Output() guardar = new EventEmitter<Paciente>();
    @Output() cancelar = new EventEmitter<void>();

    ngOnInit(): void {
        if (!this.paciente.usuario) {
            this.paciente.usuario = {
                id_usuario: 0,
                nombre: '',
                apellidos: '',
                email: '',
                telefono: '',
                rol: 'paciente',
                dni_usuario: '',
                fecha_nacimiento: '',
                fecha_creacion: '',
                fecha_actualizacion: ''
            };
        }
    }

    guardarCambios() {
        this.guardar.emit(this.paciente);
    }

    cerrar() {
        this.cancelar.emit();
    }
}
