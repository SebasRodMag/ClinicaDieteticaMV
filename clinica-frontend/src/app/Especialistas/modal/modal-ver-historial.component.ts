import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Historial } from '../../models/historial.model';
import { formatearFecha } from '../../components/utilidades/sanitizar.utils';

@Component({
    selector: 'app-modal-ver-historial',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './modal-ver-historial.component.html',
})
export class ModalVerHistorialComponent {
    @Input() visible: boolean = false;
    @Input() historial!: Historial;
    @Input() color: string = '#28a745';
    @Output() cerrar = new EventEmitter<void>();

    get cargando(): boolean {
        console.log(this.historial)
        return !this.historial?.id || !this.historial?.fecha || !this.historial?.paciente || !this.historial?.id_especialista;
    }

    get campos() {
        return [
            { label: 'Fecha', valor: formatearFecha(this.historial?.fecha ?? '') },
            { label: 'Paciente', valor: `${this.historial?.paciente?.user?.nombre ?? ''} ${this.historial?.paciente?.user?.apellidos ?? ''}`.trim() },
            { label: 'Observaciones', valor: this.historial?.observaciones_especialista ?? '' },
            { label: 'Recomendaciones', valor: this.historial?.recomendaciones ?? '' },
            { label: 'Dieta', valor: this.historial?.dieta ?? '' },
            { label: 'Lista de la compra', valor: this.historial?.lista_compra ?? '' },
        ];
        
    }

    get especialistaNombre(): string {
        return `${this.historial?.especialista?.user.nombre?? ''} ${this.historial?.especialista?.user?.apellidos ?? ''}`.trim();
    }

    get especialistaEspecialidad(): string {
        return this.historial?.especialista?.especialidad ?? '';
    }

    get nombrePaciente(): string {
        return `${this.historial?.paciente?.user?.nombre ?? ''} ${this.historial?.paciente?.user?.apellidos ?? ''}`.trim();
    }
}
