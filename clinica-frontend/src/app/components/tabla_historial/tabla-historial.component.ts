import { Component, Input, Output, EventEmitter, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Historial } from '../../models/historial.model';
import { formatearFecha } from '../utilidades/sanitizar.utils';

@Component({
    selector: 'app-tabla-historial',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './tabla-historial.component.html',
    styleUrls: ['./tabla-historial.component.css'],
})
export class TablaHistorialComponent implements OnChanges {
    @Input() historiales: Historial[] = [];
    @Input() historialSeleccionadoId: number | null = null;

    @Output() editar = new EventEmitter<Historial>();
    @Output() eliminar = new EventEmitter<Historial>();
    @Output() seleccionar = new EventEmitter<Historial>();
    @Output() ver = new EventEmitter<Historial>();

    paginaActual: number = 1;
    itemsPorPagina: number = 10;

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['historiales']) {
            this.paginaActual = 1;
        }
    }

    get historialesPaginados(): Historial[] {
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
        return this.historiales.slice(inicio, inicio + this.itemsPorPagina);
    }

    totalPaginas(): number {
        return Math.ceil(this.historiales.length / this.itemsPorPagina) || 1;
    }

    cambiarPagina(pagina: number) {
        if (pagina >= 1 && pagina <= this.totalPaginas()) {
            this.paginaActual = pagina;
        }
    }

    formatearFecha(fechaIso: string): string {
        return fechaIso ? formatearFecha(fechaIso) : '';
    }
}
