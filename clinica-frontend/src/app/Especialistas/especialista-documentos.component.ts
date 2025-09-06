import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgSelectModule } from '@ng-select/ng-select';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';

import { TablaDocumentosComponent } from '../components/tabla_documentos/tabla-documentos.component';
import { DocumentoService } from '../service/Documento-Service/documento.service';
import { HistorialService } from '../service/Historial-Service/historial.service';
import { Documento } from '../models/documento.model';

@Component({
    selector: 'app-especialista-documentos',
    standalone: true,
    imports: [CommonModule, FormsModule, NgSelectModule, MatSnackBarModule, TablaDocumentosComponent],
    templateUrl: './especialista-documentos.component.html',
})
export class EspecialistaDocumentosComponent implements OnInit {
    pacientes: Array<{ id: number; nombreCompleto: string }> = [];
    pacienteSeleccionadoId: number | null = null;

    documentosTabla: Documento[] = [];
    documentosOriginal: Documento[] = [];

    loadingPacientes = false;
    loadingDocs = false;
    filtro = '';

    constructor(
        private documentosService: DocumentoService,
        private historialService: HistorialService,
        private snack: MatSnackBar
    ) { }

    ngOnInit(): void {
        this.cargarPacientes();
    }

    cargarPacientes(): void {
        this.loadingPacientes = true;
        this.historialService.obtenerPacientesEspecialista().subscribe({
            next: (lista: any[]) => {
                this.pacientes = (lista || []).map(p => ({
                    id: Number(p.id),
                    nombreCompleto: `${p.nombre ?? ''} ${p.apellidos ?? ''}`.trim(),
                }));
                this.loadingPacientes = false;
            },
            error: () => {
                this.loadingPacientes = false;
                this.snack.open('No se pudieron cargar los pacientes', 'Cerrar', { duration: 3000 });
            }
        });
    }

    onPacienteChange(): void {
        this.filtro = '';
        this.documentosTabla = [];
        this.documentosOriginal = [];
        if (!this.pacienteSeleccionadoId) return;

        this.loadingDocs = true;
        this.documentosService.obtenerDocumentosDePaciente(this.pacienteSeleccionadoId).subscribe({
            next: (docs: Documento[]) => {
                this.documentosOriginal = docs ?? [];
                this.documentosTabla = this.documentosOriginal;
                this.loadingDocs = false;
            },
            error: () => {
                this.loadingDocs = false;
                this.snack.open('No se pudieron cargar los documentos del paciente', 'Cerrar', { duration: 3000 });
            }
        });
    }

    aplicarFiltro(): void {
        const f = this.filtro.toLowerCase().trim();
        const base = this.documentosOriginal;
        this.documentosTabla = f
            ? base.filter(d => (d.nombre || '').toLowerCase().includes(f))
            : base;
    }

    onDescargar = (row: Documento) => {
        this.documentosService.descargarDocumento(Number(row.id)).subscribe({
            next: (blob) => {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = row.nombre || 'documento';
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            },
            error: () => {
                this.snack.open('No se pudo descargar el documento', 'Cerrar', { duration: 3000 });
            }
        });
    };
}