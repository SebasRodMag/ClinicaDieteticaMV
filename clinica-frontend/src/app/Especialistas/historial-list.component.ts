import { Component, OnInit, TemplateRef, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HistorialService } from '../service/Historial-Service/historial.service';
import { Historial } from '../models/historial.model';
import { TablaHistorialComponent } from '../components/tabla_historial/tabla-datos.component';
import { ModalEditHistorialComponent } from './modal/modal-edit-historial.component';
import { FormsModule } from '@angular/forms';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { formatearFecha, formatearHora } from '../components/utilidades/sanitizar.utils';
import * as Papa from 'papaparse';
import { saveAs } from 'file-saver';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';


@Component({
    selector: 'app-historial-list',
    standalone: true,
    imports: [
        CommonModule,
        FormsModule,
        ModalEditHistorialComponent,
        TablaHistorialComponent,
        MatSnackBarModule
    ],
    templateUrl: './historial-list.component.html',
})
export class HistorialListComponent implements OnInit {
    historiales: Historial[] = [];
    loading = true;
    huboError = false;
    filtro = '';
    modalVisible = false;
    historialSeleccionado: Partial<Historial> = {};
    esNuevo = true;
    filtroFechaInicio: string = '';
    filtroFechaFin: string = '';
    pacientesUnicos: { id: number, nombre: string }[] = [];
    pacienteSeleccionadoId: number | null = null;

    columnas = ['fecha', 'paciente', 'observaciones', 'acciones'];
    templatesMap: { [key: string]: any } = {};

    constructor(
        private historialService: HistorialService,
        private snackBar: MatSnackBar
    ) { }

    ngOnInit(): void {
        this.cargarHistoriales();
    }

    cargarHistoriales() {
        this.loading = true;
        this.huboError = false;

        this.historialService.obtenerHistorialesEspecialista().subscribe({
            next: (data) => {
                this.historiales = data;
                this.procesarPacientesUnicos();
                this.loading = false;
            },
            error: (err) => {
                console.error('Error al cargar historiales', err);
                this.huboError = true;
                this.loading = false;
                this.snackBar.open('Error al cargar historiales', 'Cerrar', { duration: 3000 });
            }
        });
    }

    abrirModalNuevo() {
        this.historialSeleccionado = {};
        this.esNuevo = true;
        this.modalVisible = true;
    }

    abrirModalEditar(historial: Historial) {
        this.historialSeleccionado = { ...historial };
        this.esNuevo = false;
        this.modalVisible = true;
    }

    cerrarModal() {
        this.modalVisible = false;
    }

    guardarHistorial(historial: Partial<Historial>) {
        if (this.esNuevo) {
            this.historialService.crearHistorial(historial).subscribe({
                next: () => {
                    this.snackBar.open('Historial creado correctamente', 'Cerrar', { duration: 3000 });
                    this.cargarHistoriales();
                },
                error: (err) => {
                    console.error('Error al crear historial', err);
                    this.snackBar.open('Error al crear historial', 'Cerrar', { duration: 3000 });
                }
            });
        } else if (historial.id) {
            this.historialService.actualizarHistorial(historial.id, historial).subscribe({
                next: () => {
                    this.snackBar.open('Historial actualizado correctamente', 'Cerrar', { duration: 3000 });
                    this.cargarHistoriales();
                },
                error: (err) => {
                    console.error('Error al actualizar historial', err);
                    this.snackBar.open('Error al actualizar historial', 'Cerrar', { duration: 3000 });
                }
            });
        }
        this.limpiarFiltros();
        this.cerrarModal();
    }

    eliminarHistorial(historial: Historial) {
        if (confirm('¿Estás seguro de que deseas eliminar esta entrada del historial?')) {
            this.historialService.eliminarHistorial(historial.id!).subscribe({
                next: () => {
                    this.snackBar.open('Historial eliminado correctamente', 'Cerrar', { duration: 3000 });
                    this.cargarHistoriales();
                },
                error: (err) => {
                    console.error('Error al eliminar historial', err);
                    this.snackBar.open('Error al eliminar historial', 'Cerrar', { duration: 3000 });
                }
            });
        }
        this.limpiarFiltros();
    }

    limpiarFiltros() {
        this.filtro = '';
        this.filtroFechaInicio = '';
        this.filtroFechaFin = '';
    }

    exportarCSV() {
        if (confirm('¿Deseas exportar el historial a CSV?')) {
            const datos = this.historialesFiltrados.map(h => ({
            Fecha: h.fecha,
            Paciente: `${h.paciente?.user?.nombre ?? ''} ${h.paciente?.user?.apellidos ?? ''}`,
            Observaciones: h.observaciones_especialista ?? '',
            Recomendaciones: h.recomendaciones ?? '',
            Dieta: h.dieta ?? '',
            ListaCompra: h.lista_compra ?? ''
        }));

        const csv = Papa.unparse(datos);
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        saveAs(blob, `Historiales_${new Date().toISOString().slice(0, 10)}.csv`);
        }
        
    }

    exportarPDF() {
        if (confirm('¿Deseas exportar el historial a PDF')) {
            const doc = new jsPDF();
        doc.text('Historiales', 14, 16);

        const datos = this.historialesFiltrados.map(h => [
            h.fecha ?? '',
            `${h.paciente?.user?.nombre ?? ''} ${h.paciente?.user?.apellidos ?? ''}`,
            h.observaciones_especialista ?? '',
            h.recomendaciones ?? '',
            h.dieta ?? ''
        ]);

        autoTable(doc, {
            head: [['Fecha', 'Paciente', 'Observaciones', 'Recomendaciones', 'Dieta']],
            body: datos,
            startY: 20,
            styles: { fontSize: 8 },
        });

        doc.save(`Historiales_${new Date().toISOString().slice(0, 10)}.pdf`);
        }
        
    }

    private procesarPacientesUnicos() {
        const pacientesMap = new Map<number, string>();
        this.historiales.forEach(h => {
            if (h.paciente) {
                const id = h.paciente.id;
                const nombre = `${h.paciente.user?.nombre ?? ''} ${h.paciente.user?.apellidos ?? ''}`;
                pacientesMap.set(id, nombre);
            }
        });
        this.pacientesUnicos = Array.from(pacientesMap.entries()).map(([id, nombre]) => ({ id, nombre }));
    }

    get historialesFiltrados(): Historial[] {
        const filtroLower = this.filtro.toLowerCase().trim();

        return this.historiales.filter(historial => {
            const nombrePaciente = historial.paciente?.user?.nombre?.toLowerCase() ?? '';
            const apellidosPaciente = historial.paciente?.user?.apellidos?.toLowerCase() ?? '';
            const observaciones = historial.observaciones_especialista?.toLowerCase() ?? '';
            const recomendaciones = historial.recomendaciones?.toLowerCase() ?? '';
            const dieta = historial.dieta?.toLowerCase() ?? '';
            const coincidePaciente = !this.pacienteSeleccionadoId || historial.paciente?.id === this.pacienteSeleccionadoId;

            const fechaHistorial = historial.fecha ?? '';
            const fechaInicio = this.filtroFechaInicio;
            const fechaFin = this.filtroFechaFin;

            const coincideFiltroTexto =
                nombrePaciente.includes(filtroLower) ||
                apellidosPaciente.includes(filtroLower) ||
                observaciones.includes(filtroLower) ||
                recomendaciones.includes(filtroLower) ||
                dieta.includes(filtroLower);

            const dentroDeRango = (!fechaInicio || fechaHistorial >= fechaInicio) &&
                (!fechaFin || fechaHistorial <= fechaFin);

            return coincideFiltroTexto && dentroDeRango && coincidePaciente;
        });
    }
}