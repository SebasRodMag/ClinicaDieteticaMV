import { Component, Input, Output, EventEmitter, TemplateRef } from '@angular/core';
import { CommonModule, NgFor, NgIf, NgClass, NgTemplateOutlet, TitleCasePipe } from '@angular/common';

@Component({
    selector: 'app-tabla-datos',
    standalone: true,
    imports: [CommonModule, NgFor, NgIf, NgTemplateOutlet, TitleCasePipe],
    templateUrl: './tabla-datos.component.html',
})
export class TablaDatosComponent {
    @Input() columnas: string[] = [];
    @Input() templatesMap: { [key: string]: TemplateRef<any> } = {};
    @Input() columnaOrden: string | null = null;
    @Input() direccionOrdenAsc: boolean = true;

    @Input() datosTotales: any[] = [];
    @Input() paginaActual: number = 1;
    @Input() itemsPorPagina: number = 10;
    @Input() maxPaginasVisibles: number = 5;

    @Output() ordenar = new EventEmitter<string>();
    @Output() cambiarPagina = new EventEmitter<number>();

    trackById = (_: number, item: any) => item?.id ?? item;


    //Método que emite el número de página cuando el usuario cambia la página
    cambiarPaginaEmitida(nuevaPagina: number): void {
        if (nuevaPagina < 1) return;
        const totalPaginas = this.totalPaginas;
        if (nuevaPagina > totalPaginas) return;

        this.paginaActual = nuevaPagina;
        this.cambiarPagina.emit(nuevaPagina);
    }

    //Método para ordenar por columna
    ordenarPor(columna: string): void {
        this.ordenar.emit(columna);
    }

    //Método para saber si la columna está ordenada asc o desc y mostrar icono
    isOrdenAsc(columna: string): boolean {
        return this.columnaOrden === columna && this.direccionOrdenAsc;
    }

    isOrdenDesc(columna: string): boolean {
        return this.columnaOrden === columna && !this.direccionOrdenAsc;
    }

    get datosPaginados(): any[] {
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
        return this.datosTotales.slice(inicio, inicio + this.itemsPorPagina);
    }

    //Calcular total de páginas para paginador
    get totalPaginas(): number {
        return Math.ceil(this.datosTotales.length / this.itemsPorPagina);
    }
    //Generar un array de números de páginas para el paginador
    get paginas(): number[] {
        return Array(this.totalPaginas).fill(0).map((_, i) => i + 1);
    }

    get paginasVisibles(): number[] {
        const total = this.totalPaginas;
        const maxVisibles = this.maxPaginasVisibles;
        const pagina = this.paginaActual;

        if (total <= maxVisibles) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }

        const mitad = Math.floor(maxVisibles / 2);
        let inicio = pagina - mitad;
        let fin = pagina + mitad;

        if (inicio < 1) {
            inicio = 1;
            fin = maxVisibles;
        } else if (fin > total) {
            fin = total;
            inicio = total - maxVisibles + 1;
        }

        return Array.from({ length: fin - inicio + 1 }, (_, i) => inicio + i);
    }

    onOrdenar(columna: string) {
        this.ordenar.emit(columna);
    }

    onCambiarPagina(pagina: number) {
        if (pagina >= 1 && pagina <= this.totalPaginas) {
            this.cambiarPagina.emit(pagina);
        }
    }

    //Métodos para avanzar/retroceder página
    paginaAnterior() {
        if (this.paginaActual > 1) {
            this.onCambiarPagina(this.paginaActual - 1);
        }
    }

    paginaSiguiente() {
        if (this.paginaActual < this.totalPaginas) {
            this.onCambiarPagina(this.paginaActual + 1);
        }
    }
}
