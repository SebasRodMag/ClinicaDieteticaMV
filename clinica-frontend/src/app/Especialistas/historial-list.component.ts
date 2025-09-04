import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HistorialService } from '../service/Historial-Service/historial.service';
import { Historial } from '../models/historial.model';
import { TablaHistorialComponent } from '../components/tabla_historial/tabla-historial.component';
import { ModalEditHistorialComponent } from './modal/modal-edit-historial.component';
import { FormsModule } from '@angular/forms';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { ConfiguracionService } from '../service/Config-Service/configuracion.service';
import { ExportadorHistorialService } from '../service/Historial-Service/exportar-historial.service';
import { ModalVerHistorialComponent } from './modal/modal-ver-historial.component';

@Component({
    selector: 'app-historial-list',
    standalone: true,
    imports: [
        CommonModule,
        FormsModule,
        ModalEditHistorialComponent,
        TablaHistorialComponent,
        MatSnackBarModule,
        ModalVerHistorialComponent
    ],
    templateUrl: './historial-list.component.html',
})
export class HistorialListComponent implements OnInit {
    historiales: Historial[] = [];
    loading = true;
    huboError = false;
    filtro = '';
    modalVisible = false;
    modalVerVisible = false;
    historialSeleccionado: Partial<Historial> = {};
    historialSeleccionadoId: number | null = null;
    esNuevo = true;
    filtroFecha: string = '';
    pacientesUnicos: { id: number, nombre: string }[] = [];
    pacienteSeleccionadoId: number | null = null;
    public colorSistema: string = '#28a745';

    columnas = ['fecha', 'paciente', 'observaciones', 'acciones'];
    listaPacientesParaModal: Array<{ id: number; nombreCompleto: string }> = [];



    constructor(
        private historialService: HistorialService,
        private ConfiguracionService: ConfiguracionService,
        private exportadorHistorialService: ExportadorHistorialService,
        private snackBar: MatSnackBar
    ) { }

    ngOnInit(): void {
        this.cargarHistoriales();
        this.ConfiguracionService.cargarColorTemaPublico();
        this.ConfiguracionService.colorTema$.subscribe(color => {
            this.colorSistema = color;
        });
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
        this.historialSeleccionadoId = null;
        this.esNuevo = true;
        this.modalVisible = true;
    }

    abrirModalVer(historial: Historial) {
        this.historialSeleccionado = { ...historial };
        this.historialSeleccionadoId = historial.id!;
        this.modalVerVisible = true;
        console.log('Abriendo modal con historial:', historial);
    }

    abrirModalEditar(historial: Historial) {
        this.historialSeleccionado = { ...historial };
        this.historialSeleccionadoId = historial.id!;
        this.esNuevo = false;
        this.modalVisible = true;
    }

    cerrarModal() {
        this.modalVisible = false;
        this.historialSeleccionado = {};
        this.historialSeleccionadoId = null;
    }

    cerrarModalVer() {
        this.modalVerVisible = false;
        this.historialSeleccionado = {};
        this.historialSeleccionadoId = null;
    }

    guardarHistorial(historial: Partial<Historial>) {
        const callback = () => {
            this.cargarHistoriales();
            this.limpiarFiltros();
            this.cerrarModal();
        };

        if (this.esNuevo) {
            this.historialService.crearHistorial(historial).subscribe({
                next: () => {
                    this.snackBar.open('Historial creado correctamente', 'Cerrar', { duration: 3000 });
                    callback();
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
                    callback();
                },
                error: (err) => {
                    console.error('Error al actualizar historial', err);
                    this.snackBar.open('Error al actualizar historial', 'Cerrar', { duration: 3000 });
                }
            });
        }
    }

    eliminarHistorial(historial: Historial) {
        this.snackBar.open('¿Eliminar este historial?', 'Confirmar', {
            duration: 4000,
        }).onAction().subscribe(() => {
            this.historialService.eliminarHistorial(historial.id!).subscribe({
                next: () => {
                    this.snackBar.open('Historial eliminado correctamente', 'Cerrar', { duration: 3000 });
                    this.cargarHistoriales();
                    this.historialSeleccionado = {};
                    this.historialSeleccionadoId = null;
                },
                error: (err) => {
                    console.error('Error al eliminar historial', err);
                    this.snackBar.open('Error al eliminar historial', 'Cerrar', { duration: 3000 });
                }
            });
        });
    }

    limpiarFiltros() {
        this.filtro = '';
        this.filtroFecha = '';
        this.pacienteSeleccionadoId = null;
    }

    seleccionarHistorial(historial: Historial) {
        if (this.historialSeleccionadoId === historial.id) {
            this.historialSeleccionado = {};
            this.historialSeleccionadoId = null;
        } else {
            this.historialSeleccionado = { ...historial };
            this.historialSeleccionadoId = historial.id!;
            const nombre = `${historial.paciente?.user?.nombre ?? ''} ${historial.paciente?.user?.apellidos ?? ''}`.trim();
            this.snackBar.open(`Historial seleccionado: ${nombre || 'Sin nombre'}`, '', {
                duration: 3000,
                verticalPosition: 'top'
            });
        }
        console.log('Historial exportado:', this.historialSeleccionado);
    }

    intentarExportar(formato: 'csv' | 'pdf') {
        if (!this.esHistorialValido(this.historialSeleccionado)) {
            this.snackBar.open('El historial seleccionado no está completo para exportar', 'Cerrar', {
                duration: 3000,
                verticalPosition: 'top'
            });
            return;
        }

        const historial = this.historialSeleccionado;
        const especialistaId = historial.id_especialista;

        if (formato === 'csv') {
            this.exportadorHistorialService.exportarCSV(historial);
        } else {
            this.exportadorHistorialService.exportarPDF(historial, especialistaId);
        }
    }

    public esHistorialValido(historial: Partial<Historial>): historial is Historial {
        return !!(
            historial.id &&
            historial.fecha &&
            historial.paciente &&
            historial.id_especialista
        );
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

    private normalizarIdPacienteDePayload(payload: {
        id_paciente: number | null | string | undefined;
        nombre_paciente?: string;
    }): { id: number | null; nombre: string } {
        let id: number | null = null;
        if (payload.id_paciente !== undefined && payload.id_paciente !== null && payload.id_paciente !== '') {
            const posible = Number(payload.id_paciente);
            id = Number.isFinite(posible) ? posible : null;
        }
        const nombre = (payload.nombre_paciente || '').trim();
        return { id, nombre };
    }

    private buildListaPacientesBase(): Array<{ id: number; nombreCompleto: string }> {
        // Puedes construirla desde pacientesUnicos…
        return (this.pacientesUnicos || []).map(p => ({ id: p.id, nombreCompleto: p.nombre }));
    }

    private ensurePacienteEnLista(id: number | null, nombre: string) {
        if (!this.listaPacientesParaModal) this.listaPacientesParaModal = [];
        if (id && !this.listaPacientesParaModal.some(p => p.id === id)) {
            this.listaPacientesParaModal.push({ id, nombreCompleto: nombre || 'Paciente seleccionado' });
        }
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

            const coincideFiltroTexto =
                nombrePaciente.includes(filtroLower) ||
                apellidosPaciente.includes(filtroLower) ||
                observaciones.includes(filtroLower) ||
                recomendaciones.includes(filtroLower) ||
                dieta.includes(filtroLower);

            const coincideFecha = !this.filtroFecha || historial.fecha === this.filtroFecha;

            return coincideFiltroTexto && coincideFecha && coincidePaciente;
        });
    }

    get pacientesUnicosMapeados(): Array<{ id: number; nombreCompleto: string }> {
        return this.pacientesUnicos.map(p => ({ id: p.id, nombreCompleto: p.nombre }));
    }

    get pacienteNombreSeleccionado(): string {
        const p = this.historialSeleccionado?.paciente;
        return p ? `${p.user?.nombre ?? ''} ${p.user?.apellidos ?? ''}`.trim() : '';
    }
}
