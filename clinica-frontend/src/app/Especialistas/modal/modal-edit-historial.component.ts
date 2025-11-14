import { Component, EventEmitter, Input, Output, OnInit, SimpleChanges, OnChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NgSelectModule } from '@ng-select/ng-select';
import { Historial } from '../../models/historial.model';
import { HistorialService } from '../../service/Historial-Service/historial.service';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';

@Component({
    selector: 'app-modal-edit-historial',
    standalone: true,
    imports: [CommonModule, FormsModule, NgSelectModule, MatSnackBarModule],
    templateUrl: './modal-edit-historial.component.html'
})
export class ModalEditHistorialComponent implements OnChanges, OnInit {
    @Input() visible: boolean = false;
    @Input() esNuevo: boolean = false;
    @Input() historial: Partial<Historial> = {};
    @Input() color: string = '#b7bbc2ff';
    @Input() pacienteNombre: string = '';
    @Input() listaPacientes: any[] | null = null;
    @Output() cerrar = new EventEmitter<void>();
    @Output() guardar = new EventEmitter<Partial<Historial>>();

    pacientes: any[] = [];
    pacienteSeleccionado: any = null;
    fechaActual: string = new Date().toISOString().split('T')[0];

    cargandoPacientes = false;

    constructor(
        private historialService: HistorialService,
        private snackBar: MatSnackBar
    ) { }

    ngOnInit(): void { }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['visible'] && this.visible) {
            if (!this.historial.fecha) {
                this.historial.fecha = this.fechaActual;
            }
            // Si es nuevo y viene id_paciente, se pre selecciona y evita carga completa
            const id = this.historial.id_paciente != null ? Number(this.historial.id_paciente) : NaN;
            if (this.esNuevo && Number.isFinite(id)) {
                // construimos una lista mínima con sólo ese paciente
                const etiqueta = (this.pacienteNombre || 'Paciente seleccionado').trim();
                this.pacientes = [{ id, nombreCompleto: etiqueta }];
                this.cargandoPacientes = false;
                // Asegura selección y resumen
                this.preseleccionarPaciente();
                this.mostrarResumenPaciente();
                return;
            }

            if (this.listaPacientes && this.listaPacientes.length) {
                this.pacientes = this.listaPacientes.map((p: any) => ({
                    id: Number(p.id),
                    nombreCompleto: p.nombreCompleto ?? `${p.nombre ?? ''} ${p.apellidos ?? ''}`.trim()
                }));
                this.aseguraseIdPacientePresente();
                this.cargandoPacientes = false;
                this.preseleccionarPaciente();
                this.mostrarResumenPaciente();
            } else {
                this.cargarPacientes();
            }
        }

        if (changes['historial'] && this.visible && this.pacientes.length) {
            this.preseleccionarPaciente();
            this.mostrarResumenPaciente();
        }
    }

    cargarPacientes(): void {
        this.cargandoPacientes = true;
        this.historialService.obtenerPacientesEspecialista().subscribe({
            next: (data) => {
                this.pacientes = (data as any[]).map(p => ({
                    id: Number(p.id),
                    nombreCompleto: `${p.nombre ?? ''} ${p.apellidos ?? ''}`.trim()
                }));
                this.aseguraseIdPacientePresente();
                this.preseleccionarPaciente();
                this.mostrarResumenPaciente();
                this.cargandoPacientes = false;
            },
            error: () => {
                this.snackBar.open('Error al cargar pacientes', 'Cerrar', { duration: 3000 });
                this.cargandoPacientes = false;
            }
        });
    }

    private preseleccionarPaciente(): void {
        const nestedId = (this.historial as any)?.paciente?.id;
        if (nestedId) this.historial.id_paciente = Number(nestedId);

        const id = this.historial.id_paciente != null ? Number(this.historial.id_paciente) : null;
        if (id && this.pacientes.some(p => Number(p.id) === id)) {
            this.historial.id_paciente = id;
            return;
        }

        const nombre = (this.pacienteNombre || '').trim().toLowerCase();
        if (nombre) {
            const encontrado = this.pacientes.find(p => (p.nombreCompleto || '').trim().toLowerCase() === nombre);
            if (encontrado) {
                this.historial.id_paciente = Number(encontrado.id);
                return;
            }
        }
    }

    mostrarResumenPaciente(): void {
        const id = this.historial.id_paciente ? Number(this.historial.id_paciente) : null;
        this.pacienteSeleccionado = id ? this.pacientes.find(p => Number(p.id) === id) ?? null : null;
    }

    calcularEdad(fechaNacimiento: string): number {
        const hoy = new Date();
        const nacimiento = new Date(fechaNacimiento);
        let edad = hoy.getFullYear() - nacimiento.getFullYear();
        const mes = hoy.getMonth() - nacimiento.getMonth();
        if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
            edad--;
        }
        return edad;
    }

    esFormularioValido(): boolean {
        const {
            id_paciente,
            fecha,
            observaciones_especialista,
            recomendaciones,
            dieta,
            lista_compra
        } = this.historial;

        const fechaValida = !!fecha && new Date(fecha) <= new Date(this.fechaActual);

        const hayContenido = [
            observaciones_especialista?.trim() ?? '',
            recomendaciones?.trim() ?? '',
            dieta?.trim() ?? '',
            lista_compra?.trim() ?? ''
        ].some(campo => campo.length > 0);

        return !!id_paciente && fechaValida && hayContenido;
    }

    //Asegurar que el id_paciente del historial esté en this.pacientes
    //Esto depende desde donde se esta pasando, ya que el mismo modal puede usarse en varios contextos
    private aseguraseIdPacientePresente(): void {
        const id = this.historial.id_paciente != null ? Number(this.historial.id_paciente) : NaN;
        if (!Number.isFinite(id)) return;

        const yaEsta = this.pacientes.some(p => Number(p.id) === id);
        if (!yaEsta) {
            //Se usa pacienteNombre como etiqueta si no tenemos más info
            const etiqueta = (this.pacienteNombre || 'Paciente seleccionado').trim();
            this.pacientes.push({ id, nombreCompleto: etiqueta });
        }
    }

}
