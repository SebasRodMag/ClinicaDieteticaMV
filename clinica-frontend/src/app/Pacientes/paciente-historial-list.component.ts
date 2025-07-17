import { Component, OnInit, ViewChild, TemplateRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Historial } from '../models/historial.model';
import { HistorialService } from '../service/Historial-Service/historial.service';
import { ModalVerHistorialComponent } from '../Especialistas/modal/modal-ver-historial.component';//Por probar, utilizo el mismo modal de especialista
import { TablaDatosComponent } from '../components/tabla_datos/tabla-datos.component';
import { ExportadorHistorialService } from '../service/Historial-Service/exportar-historial.service';
import { FormsModule } from '@angular/forms';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { Router, RouterModule } from '@angular/router';

@Component({
    selector: 'app-paciente-historiales',
    standalone: true,
    imports: [
        CommonModule,
        FormsModule,
        ModalVerHistorialComponent,
        TablaDatosComponent,
        MatSnackBarModule,
        RouterModule
    ],
    templateUrl: './paciente-historial-list.component.html',
})
export class PacienteHistorialListComponent implements OnInit {
    historialesTotales: Historial[] = [];
    historialesFiltrados: Historial[] = [];
    historialSeleccionado!: Historial;
    modalVisible = false;
    loading = true;
    filtroTexto = '';

    // Paginación
    paginaActual = 1;
    itemsPorPagina = 5;
    maxPaginasVisibles = 5;

    // Ordenación
    columnaOrden = 'fecha';
    direccionOrdenAsc = false;
    public colorSistema: string = '#28a745';


    constructor(
        private historialService: HistorialService,
        private exportadorService: ExportadorHistorialService,
        private snackBar: MatSnackBar,
        private router: Router
    ) { }

    ngOnInit(): void {
        this.cargarHistoriales();
    }

    cargarHistoriales(): void {
        this.historialService.obtenerMisHistorialesPaciente().subscribe({
            next: (res) => {
                this.historialesTotales = res;
                this.aplicarFiltros();
                this.loading = false;
            },
            error: () => {
                this.loading = false;
            },
        });
    }

    aplicarFiltros(): void {
        const filtro = this.filtroTexto.toLowerCase().trim();

        this.historialesFiltrados = this.historialesTotales.filter((h) =>
            h.especialista?.user?.nombre?.toLowerCase().includes(filtro) ||
            h.especialista?.user?.apellidos?.toLowerCase().includes(filtro) ||
            h.especialista?.especialidad?.toLowerCase().includes(filtro) ||
            h.fecha?.toLowerCase().includes(filtro)
        );
    }

    verHistorial(historial: Historial): void {
        this.historialSeleccionado = historial;
        this.modalVisible = true;
    }

    cerrarModal(): void {
        this.modalVisible = false;
    }

    exportarPDF(historial: Historial): void {
        this.exportadorService.exportarPDF(historial);
        this.snackBar.open('PDF exportado correctamente.', 'Cerrar', { duration: 3000 });
    }


    exportarCSV(historial: Historial): void {
        this.exportadorService.exportarCSV(historial);
        this.snackBar.open('CSV exportado correctamente.', 'Cerrar', { duration: 3000 });
    }

    exportarTodosPDF(): void {
        if (this.historialesFiltrados.length > 0) {
            this.exportadorService.exportarPDF(this.historialesFiltrados);
            this.snackBar.open('Todos los historiales se exportaron en PDF.', 'Cerrar', { duration: 3000 });
        } else {
            this.snackBar.open('No hay historiales para exportar.', 'Cerrar', { duration: 3000 });
        }
    }

    exportarTodosCSV(): void {
        if (this.historialesFiltrados.length > 0) {
            this.exportadorService.exportarCSV(this.historialesFiltrados);
            this.snackBar.open('Todos los historiales se exportaron en CSV.', 'Cerrar', { duration: 3000 });
        } else {
            this.snackBar.open('No hay historiales para exportar.', 'Cerrar', { duration: 3000 });
        }
    }

    ordenarPor(campo: string): void {
        if (this.columnaOrden === campo) {
            this.direccionOrdenAsc = !this.direccionOrdenAsc;
        } else {
            this.columnaOrden = campo;
            this.direccionOrdenAsc = true;
        }
    }

    cambiarPagina(pagina: number): void {
        this.paginaActual = pagina;
    }
}
