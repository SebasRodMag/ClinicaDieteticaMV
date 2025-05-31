import {
    Component,
    Input,
    Output,
    EventEmitter,
    TemplateRef,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
    selector: 'app-tabla-datos',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './tabla-datos.component.html',
})
export class TablaDatosComponent {
    @Input() datos: any[] = [];
    @Input() columnas: string[] = [];
    @Input() plantillaColumnas: { [clave: string]: any } = {};

    @Input() filasPorPagina: number = 10;
    @Input() paginaActual: number = 1;
    @Input() orden: string | null = null;
    @Input() ascendente: boolean = true;

    @Input() templatesMap: { [key: string]: TemplateRef<any> } = {};

    @Output() ordenar = new EventEmitter<string>();
    @Output() cambiarPagina = new EventEmitter<number>();
    get datosPaginados(): any[] {
        const inicio = (this.paginaActual - 1) * this.filasPorPagina;
        return this.datos.slice(inicio, inicio + this.filasPorPagina);
    }

    get totalPaginas(): number {
        return Math.ceil(this.datos.length / this.filasPorPagina);
    }

    get paginas(): number[] {
        return Array.from({ length: this.totalPaginas }, (_, i) => i + 1);
    }

    cambiarOrden(columna: string): void {
        this.ordenar.emit(columna);
    }

    getIconoOrden(columna: string): string {
        if (this.orden !== columna) return '↕';
        return this.ascendente ? '↑' : '↓';
    }
}
