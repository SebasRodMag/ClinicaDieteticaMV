import { Component, Input, Output, EventEmitter, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { formatearFecha } from '../utilidades/sanitizar.utils';

@Component({
    selector: 'app-tabla-documentos',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './tabla-documentos.component.html',
})
export class TablaDocumentosComponent {
    @Input() documentos: any[] = [];
    @Input() mostrarDescargar: boolean = false;
    @Input() mostrarEliminar: boolean = false;

    @Output() descargar = new EventEmitter<any>();
    @Output() eliminar = new EventEmitter<any>();

    columnas: string[] = [];
    camposOcultos: string[] = ['id', 'archivo', 'user_id', 'historial_id', 'deleted_at', 'created_at', 'updated_at'];
    formatearFecha: any = formatearFecha;

    //Estos campos no se mostraran automáticamente
    ngOnChanges(changes: SimpleChanges): void {
        if (changes['documentos'] && this.documentos.length > 0) {
            const keys = Object.keys(this.documentos[0]);
            this.columnas = keys.filter(key => !this.camposOcultos.includes(key));
        }
    }

    //aplicamos un diccionario para mostrar los títulos de las columnas
    etiquetasColumnas: { [key: string]: string } = {
        nombre: 'Nombre',
        tipo: 'Tipo',
        tamano: 'Tamaño',
        descripcion: 'Descripción',
        created_at: 'Fecha de subida',
        updated_at: 'Última modificación',
    };
}
