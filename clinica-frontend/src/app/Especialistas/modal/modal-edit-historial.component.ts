import { Component, EventEmitter, Input, Output, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Historial } from '../../models/historial.model';
import { MatSnackBarModule, MatSnackBar } from '@angular/material/snack-bar';

@Component({
    selector: 'app-modal-edit-historial',
    standalone: true,
    imports: [CommonModule, FormsModule, MatSnackBarModule],
    templateUrl: './modal-edit-historial.component.html',
})
export class ModalEditHistorialComponent implements OnChanges {
    @Input() visible: boolean = false;
    @Input() historial: Partial<Historial> = {};
    @Input() esNuevo: boolean = true;

    @Output() cerrar = new EventEmitter<void>();
    @Output() guardar = new EventEmitter<Partial<Historial>>();

    errores: { [key: string]: string } = {};

    constructor(private snackBar: MatSnackBar) {}

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['historial'] && this.historial) {
            this.historial = { ...this.historial };
            this.errores = {};
        }
    }

    onSubmit() {
        this.errores = {};

        if (!this.historial.fecha) {
            this.errores['fecha'] = 'La fecha es obligatoria';
        }

        if (Object.keys(this.errores).length > 0) {
            this.snackBar.open('Hay errores en el formulario', 'Cerrar', { duration: 3000 });
            return;
        }

        this.guardar.emit({ ...this.historial });
        this.snackBar.open('Historial guardado correctamente', 'Cerrar', { duration: 3000 });
        this.cerrarModal();
    }

    cerrarModal() {
        this.cerrar.emit();
        this.errores = {};
    }

    validarCampo(campo: keyof Historial): void {
        const valor = this.historial[campo];
        this.errores[campo] = '';

        if (campo === 'fecha' && !valor) {
            this.errores[campo] = 'La fecha es obligatoria';
        }
    }
}