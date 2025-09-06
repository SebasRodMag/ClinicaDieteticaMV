import { Component, OnInit, OnDestroy } from '@angular/core';
import { Subject, of } from 'rxjs';
import { debounceTime, distinctUntilChanged, switchMap, tap, catchError, finalize, filter, takeUntil } from 'rxjs/operators';
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
    imports: [CommonModule, FormsModule, NgSelectModule, MatSnackBarModule, TablaDocumentosComponent,],
    templateUrl: './especialista-documentos.component.html',
})
export class EspecialistaDocumentosComponent implements OnInit, OnDestroy {
    pacientes: Array<{ id: number; nombreCompleto: string }> = [];
    pacienteSeleccionadoId: number | null = null;

    documentosTabla: Documento[] = [];
    documentosOriginal: Documento[] = [];

    cargandoPacientes = false;
    cargarDocumentos = false;
    filtro = '';

    private seleccionarPaciente$ = new Subject<number | null>();
    private eliminarSeleccion$ = new Subject<void>();

    constructor(
        private documentosService: DocumentoService,
        private historialService: HistorialService,
        private snack: MatSnackBar
    ) { }

    ngOnInit(): void {
        this.cargarPacientes();
        this.initFlujoSeleccionPaciente();
    }

    ngOnDestroy(): void {
        this.eliminarSeleccion$.next();
        this.eliminarSeleccion$.complete();
    }

    private initFlujoSeleccionPaciente(): void {
        this.seleccionarPaciente$
            .pipe(
                // Limpieza previa y sincronizar modelo
                tap((id) => {
                    this.pacienteSeleccionadoId = id ?? null;
                    this.filtro = '';
                    this.documentosTabla = [];
                    this.documentosOriginal = [];
                }),
                // Si es null/0, no disparamos llamada
                filter((id): id is number => !!id),
                // Evita peticiones redundantes si re-seleccionan el mismo id
                distinctUntilChanged(),
                // (Opcional) suaviza cambios muy rápidos
                debounceTime(150),
                tap(() => (this.cargarDocumentos = true)),
                switchMap((id) =>
                    this.documentosService.obtenerDocumentosDePaciente(id).pipe(
                        catchError(() => {
                            this.snack.open('No se pudieron cargar los documentos del paciente', 'Cerrar', { duration: 3000 });
                            return of<Documento[]>([]);
                        }),
                        finalize(() => (this.cargarDocumentos = false))
                    )
                ),
                takeUntil(this.eliminarSeleccion$)
            )
            .subscribe((docs) => {
                this.documentosOriginal = docs ?? [];
                this.documentosTabla = this.documentosOriginal;
            });
    }

    cargarPacientes(): void {
        this.cargandoPacientes = true;
        this.historialService.obtenerPacientesEspecialista().subscribe({
            next: (lista: any[]) => {
                this.pacientes = (lista || []).map(p => ({
                    id: Number(p.id),
                    nombreCompleto: `${p.nombre ?? ''} ${p.apellidos ?? ''}`.trim(),
                }));
                this.cargandoPacientes = false;
            },
            error: () => {
                this.cargandoPacientes = false;
                this.snack.open('No se pudieron cargar los pacientes', 'Cerrar', { duration: 3000 });
            }
        });
    }

    seleccionarPaciente(id: number | null): void {
        this.seleccionarPaciente$.next(id);
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
