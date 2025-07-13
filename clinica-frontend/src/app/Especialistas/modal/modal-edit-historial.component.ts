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
export class ModalEditHistorialComponent implements OnInit, OnChanges {
    @Input() visible: boolean = false;
    @Input() esNuevo: boolean = false;
    @Input() historial: Partial<Historial> = {};
    @Input() color: string = '#28a745';
    @Output() cerrar = new EventEmitter<void>();
    @Output() guardar = new EventEmitter<Partial<Historial>>();

    pacientes: any[] = [];
    pacienteSeleccionado: any = null;
    fechaActual: string = new Date().toISOString().split('T')[0];

    constructor(
        private historialService: HistorialService,
        private snackBar: MatSnackBar
    ) { }

    ngOnInit(): void {
        this.cargarPacientes();
    }

    ngOnChanges(changes: SimpleChanges): void {
        if (changes['visible'] && this.visible) {
            this.cargarPacientes();
        }
    }

    cargarPacientes(): void {
        this.historialService.obtenerPacientesEspecialista().subscribe({
            next: (data) => {
                this.pacientes = (data as any[]).map(p => ({
                    ...p,
                    nombreCompleto: `${p.nombre} ${p.apellidos}`.trim()
                }));
                this.mostrarResumenPaciente();
            },
            error: () => {
                this.snackBar.open('Error al cargar pacientes', 'Cerrar', { duration: 3000 });
            }
        });
    }

    mostrarResumenPaciente(): void {
        this.pacienteSeleccionado = this.pacientes.find(p => p.id === this.historial.id_paciente) ?? null;
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

        const fechaValida = !!fecha && new Date(fecha) >= new Date(this.fechaActual);

        const hayContenido = [
            observaciones_especialista?.trim() ?? '',
            recomendaciones?.trim() ?? '',
            dieta?.trim() ?? '',
            lista_compra?.trim() ?? ''
        ].some(campo => campo.length > 0);

        return !!id_paciente && fechaValida && hayContenido;
    }

}
