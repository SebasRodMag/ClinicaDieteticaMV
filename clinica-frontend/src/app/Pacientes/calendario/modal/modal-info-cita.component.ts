import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CitaPorEspecialista } from '../../../models/citasPorEspecialista.model';

@Component({
    selector: 'app-modal-info-cita',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './modal-info-cita.component.html',
})
export class ModalInfoCitaComponent {
    @Input() citaSeleccionada!: CitaPorEspecialista;
    @Input() colorSistema: string = '#0d6efd';
    @Output() cerrado = new EventEmitter<void>();
    @Output() cancelar = new EventEmitter<number>();

    puedeCancelar: boolean = false;


    ngOnInit(): void {
        this.evaluarCancelacion();
    }

    cerrarModal(): void {
        this.cerrado.emit();
    }

    cancelarCita(): void {
        if (this.citaSeleccionada) {
            this.cancelar.emit(this.citaSeleccionada.id);
        }
    }

    evaluarCancelacion(): void {
        if (!this.citaSeleccionada || this.citaSeleccionada.estado !== 'pendiente') {
            this.puedeCancelar = false;
            return;
        }

        const fechaHoraCita = new Date(`${this.citaSeleccionada.fecha}T${this.citaSeleccionada.hora}`);
        const ahora = new Date();

        const diffHoras = (fechaHoraCita.getTime() - ahora.getTime()) / (1000 * 60 * 60);

        this.puedeCancelar = diffHoras > 24;
    }
}
